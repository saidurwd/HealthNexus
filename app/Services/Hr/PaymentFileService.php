<?php

namespace App\Services\Hr;

use App\Models\Hr\HrPaymentBatch;
use App\Models\Hr\HrPayrollLine;
use App\Models\Hr\HrPayrollRun;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

/**
 * Salary payment files (S10): a bank advice and a mobile-wallet list, built from the approved run. A person with a bank account is paid by bank, otherwise by wallet; a
 * person with neither is an exception, never silently skipped. Account numbers are decrypted only here, behind a permission, and every download is audited.
 */
class PaymentFileService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function prepare(HrPayrollRun $run, string $method, User $by): HrPaymentBatch
    {
        if (! in_array($run->status, ['approved', 'paid'], true)) {
            throw ValidationException::withMessages(['status' => 'Payment files are made from an approved run.']);
        }
        if (! in_array($method, ['bank', 'mobile_wallet'], true)) {
            throw ValidationException::withMessages(['method' => 'Use bank or mobile_wallet.']);
        }
        if (HrPaymentBatch::query()->where(['run_id' => $run->id, 'method' => $method])->exists()) {
            throw ValidationException::withMessages(['method' => 'That payment file was already prepared.']);
        }
        $rows = $this->rows($run, $method);

        return HrPaymentBatch::create([
            'organization_id' => $run->organization_id, 'run_id' => $run->id, 'method' => $method, 'payee_count' => count($rows),
            'total' => array_reduce($rows, fn ($c, $r) => bcadd($c, $r['amount'], 2), '0.00'), 'file_hash' => hash('sha256', $this->csv($rows, $method)), 'created_by' => $by->id,
        ]);
    }

    /** The file contents, rebuilt from the run each time; the hash proves it is what was prepared. */
    public function download(HrPaymentBatch $batch, User $by): string
    {
        $csv = $this->csv($this->rows($batch->run, $batch->method), $batch->method);
        if (! hash_equals($batch->file_hash, hash('sha256', $csv))) {
            throw ValidationException::withMessages(['file' => 'The data has changed since this file was prepared; prepare it again.']);
        }
        $this->audit->log('PAYMENT_FILE_DOWNLOADED', HrPaymentBatch::class, $batch->id, null, ['method' => $batch->method, 'payees' => $batch->payee_count, 'by' => $by->id]);

        return $csv;
    }

    /** @return list<array{employee_no: string, name: string, account: string, bank: string, amount: string}> */
    private function rows(HrPayrollRun $run, string $method): array
    {
        $rows = [];
        foreach (HrPayrollLine::query()->with('employee')->where('run_id', $run->id)->where('net', '>', 0)->orderBy('id')->get() as $line) {
            $e = $line->employee;
            $byBank = (bool) $e->bank_account;
            if (($method === 'bank' && ! $byBank) || ($method === 'mobile_wallet' && ($byBank || ! $e->mobile_wallet))) {
                continue;
            }
            $rows[] = ['employee_no' => $e->employee_no, 'name' => $e->fullName(), 'account' => (string) ($method === 'bank' ? $e->bank_account : $e->mobile_wallet), 'bank' => (string) ($method === 'bank' ? $e->bank_name : 'wallet'), 'amount' => (string) $line->net];
        }

        return $rows;
    }

    /** @param  list<array<string, string>>  $rows */
    private function csv(array $rows, string $method): string
    {
        $out = fopen('php://memory', 'w+');
        fputcsv($out, ['Employee no', 'Name', $method === 'bank' ? 'Bank' : 'Channel', 'Account', 'Amount']);
        foreach ($rows as $r) {
            fputcsv($out, array_map(fn ($v) => preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v, [$r['employee_no'], $r['name'], $r['bank'], $r['account'], $r['amount']]));
        }
        rewind($out);
        $csv = (string) stream_get_contents($out);
        fclose($out);

        return $csv;
    }
}
