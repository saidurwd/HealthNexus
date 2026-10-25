<?php

namespace App\Services\Import;

use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ImportService
{
    public function __construct(
        private readonly ImportRegistry $registry,
        private readonly CsvReader $reader,
        private readonly AuditLogger $audit,
    ) {}

    /** Step 1 — read the file into staging rows. Nothing is validated against the database yet. */
    public function upload(User $user, ImportContext $context, string $key, string $path, string $originalName): ImportBatch
    {
        $definition = $this->registry->find($key);
        $this->authorize($user, $definition->permission());

        ['header' => $header, 'rows' => $rows] = $this->reader->open($path);

        $missing = array_values(array_diff(
            array_column(array_filter($definition->columns(), fn ($c) => $c['required']), 'name'),
            $header,
        ));
        if ($missing !== []) {
            throw ValidationException::withMessages(['file' => 'Missing required column(s): '.implode(', ', $missing).'. Download the template to see the expected columns.']);
        }

        $batch = ImportBatch::create([
            'organization_id' => $context->organizationId, 'branch_id' => $context->branchId, 'user_id' => $user->id,
            'import_key' => $key, 'original_name' => mb_substr($originalName, 0, 255), 'header' => array_values(array_filter($header)),
            'options' => $context->options,
        ]);

        $buffer = [];
        $count = 0;
        foreach ($rows as $number => $row) {
            if (++$count > config('imports.max_rows')) {
                $batch->delete();
                throw ValidationException::withMessages(['file' => 'The file has more than '.config('imports.max_rows').' rows. Split it into smaller files.']);
            }
            $buffer[] = ['import_batch_id' => $batch->id, 'row_number' => $number, 'raw' => json_encode($row, JSON_UNESCAPED_UNICODE), 'status' => 'pending'];
            if (count($buffer) === 500) {
                DB::table('import_rows')->insert($buffer);
                $buffer = [];
            }
        }
        $buffer !== [] && DB::table('import_rows')->insert($buffer);

        if ($count === 0) {
            $batch->delete();
            throw ValidationException::withMessages(['file' => 'The file has a header but no data rows.']);
        }

        $batch->update(['total_rows' => $count]);
        $this->audit->log('IMPORT_UPLOADED', ImportBatch::class, $batch->id, null, ['import' => $key, 'rows' => $count, 'file' => $originalName]);

        return $batch;
    }

    /** Step 2 — check every row: field rules, duplicates inside the file, duplicates of existing records. */
    public function validate(ImportBatch $batch, ?ImportContext $context = null): ImportBatch
    {
        $definition = $this->registry->find($batch->import_key);
        $context ??= $this->contextFor($batch);
        $batch->update(['status' => 'validating', 'error' => null]);

        $seen = [];
        $counts = ['valid' => 0, 'error' => 0, 'duplicate' => 0];

        try {
            ImportRow::query()->where('import_batch_id', $batch->id)->orderBy('row_number')->chunkById(500, function ($rows) use ($definition, $context, &$seen, &$counts) {
                foreach ($rows as $row) {
                    $result = $definition->validate($row->raw, $context);
                    $status = 'valid';
                    $duplicateOf = null;
                    $errors = $result['errors'];
                    $key = $errors === [] ? $definition->fileKey($result['data']) : null;

                    if ($errors !== []) {
                        $status = 'error';
                    } elseif ($key !== null && isset($seen[$key])) {
                        $status = 'duplicate';
                        $duplicateOf = "row {$seen[$key]} of this file";
                    } elseif ($existing = $definition->findExisting($result['data'], $context)) {
                        $status = 'duplicate';
                        $duplicateOf = $existing;
                    } elseif ($key !== null) {
                        $seen[$key] = $row->row_number;
                    }

                    $counts[$status]++;
                    $row->update(['data' => $result['data'], 'status' => $status, 'errors' => $errors ?: null, 'duplicate_of' => $duplicateOf]);
                }
            });
        } catch (Throwable $e) {
            $batch->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500)]);

            throw $e;
        }

        $batch->update([
            'status' => 'validated', 'valid_rows' => $counts['valid'], 'error_rows' => $counts['error'], 'duplicate_rows' => $counts['duplicate'],
            'validated_at' => now(),
        ]);

        return $batch->refresh();
    }

    /** Step 4 — write the valid rows. A row that fails is reported and skipped; the rest still import. */
    public function commit(ImportBatch $batch, User $user): ImportBatch
    {
        $definition = $this->registry->find($batch->import_key);
        $this->authorize($user, $definition->permission());

        if ($batch->status !== 'validated') {
            throw ValidationException::withMessages(['batch' => 'Validate the file first; only a validated batch can be imported.']);
        }

        $context = $this->contextFor($batch, $user);
        $batch->update(['status' => 'committing']);
        $imported = 0;

        ImportRow::query()->where('import_batch_id', $batch->id)->where('status', 'valid')->orderBy('row_number')->chunkById(200, function ($rows) use ($definition, $context, &$imported) {
            foreach ($rows as $row) {
                try {
                    $target = DB::transaction(fn () => $definition->commit($row->data, $context));
                    $row->update(['status' => 'imported', 'target_type' => $target['type'], 'target_id' => $target['id']]);
                    $imported++;
                } catch (Throwable $e) {
                    $row->update(['status' => 'error', 'errors' => [mb_substr($e->getMessage(), 0, 300)]]);
                }
            }
        });

        $batch->update([
            'status' => 'committed', 'imported_rows' => $imported, 'committed_at' => now(),
            'error_rows' => ImportRow::where('import_batch_id', $batch->id)->where('status', 'error')->count(),
        ]);
        $this->audit->log('IMPORT_COMMITTED', ImportBatch::class, $batch->id, null, ['import' => $batch->import_key, 'imported' => $imported]);

        return $batch->refresh();
    }

    /** Step 6 — take back a whole batch. Records that are already in use are deactivated or kept, never deleted from under users. */
    public function rollback(ImportBatch $batch, User $user): ImportBatch
    {
        $definition = $this->registry->find($batch->import_key);
        $this->authorize($user, $definition->permission());

        if ($batch->status !== 'committed') {
            throw ValidationException::withMessages(['batch' => 'Only an imported batch can be rolled back.']);
        }

        $context = $this->contextFor($batch, $user);

        ImportRow::query()->where('import_batch_id', $batch->id)->where('status', 'imported')->orderByDesc('row_number')->chunkById(200, function ($rows) use ($definition, $context) {
            foreach ($rows as $row) {
                $outcome = DB::transaction(fn () => $definition->rollback($row->target_type, $row->target_id, $context));
                $row->update(['status' => $outcome === 'kept' ? 'kept' : 'rolled_back', 'note' => $outcome]);
            }
        }, 'id');

        $batch->update(['status' => 'rolled_back', 'rolled_back_at' => now()]);
        $this->audit->log('IMPORT_ROLLED_BACK', ImportBatch::class, $batch->id, null, ['import' => $batch->import_key]);

        return $batch->refresh();
    }

    /** CSV of every row that did not import, with the reason, in the original column order. */
    public function errorFile(ImportBatch $batch): StreamedResponse
    {
        $header = $batch->header ?? [];

        return response()->streamDownload(function () use ($batch, $header) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_merge(['row_number', 'status', 'problem'], $header));

            ImportRow::query()->where('import_batch_id', $batch->id)->whereIn('status', ['error', 'duplicate', 'kept'])->orderBy('row_number')
                ->each(function (ImportRow $row) use ($out, $header) {
                    $problem = $row->status === 'duplicate' ? 'Duplicate of '.$row->duplicate_of : implode('; ', $row->errors ?? [$row->note ?? '']);
                    fputcsv($out, array_merge([$row->row_number, $row->status, $problem], array_map(fn ($h) => $this->safe($row->raw[$h] ?? ''), $header)));
                });

            fclose($out);
        }, "import-{$batch->public_id}-errors.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function template(string $key): StreamedResponse
    {
        $definition = $this->registry->find($key);
        $columns = $definition->columns();

        return response()->streamDownload(function () use ($columns) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_column($columns, 'name'));
            fputcsv($out, array_column($columns, 'example'));
            fclose($out);
        }, "import-template-{$key}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function contextFor(ImportBatch $batch, ?User $user = null): ImportContext
    {
        return new ImportContext($batch->organization_id, $batch->branch_id, $user ?? $batch->user, $batch->options ?? []);
    }

    private function authorize(User $user, string $permission): void
    {
        if (! $user->can($permission)) {
            throw new AuthorizationException('You are not allowed to run this import.');
        }
    }

    private function safe(string $value): string
    {
        return $value !== '' && in_array($value[0], ['=', '+', '-', '@'], true) ? "'".$value : $value;
    }
}
