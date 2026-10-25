<?php

namespace App\Services\Import\Definitions;

use App\Models\Laboratory\LabTest;
use App\Services\Import\ImportContext;
use Illuminate\Support\Facades\DB;

class LabTestImport extends BaseImport
{
    private const TYPES = ['quantitative', 'qualitative', 'semi_quantitative', 'text', 'categorical', 'calculated', 'microbiology', 'culture', 'pathology'];

    public function key(): string
    {
        return 'lab_tests';
    }

    public function title(): string
    {
        return 'Laboratory tests';
    }

    public function permission(): string
    {
        return 'import.lab_tests';
    }

    public function columns(): array
    {
        return [
            ['name' => 'code', 'required' => true, 'example' => 'CBC'],
            ['name' => 'name', 'required' => true, 'example' => 'Complete Blood Count'],
            ['name' => 'short_name', 'required' => false, 'example' => 'CBC'],
            ['name' => 'test_type', 'required' => false, 'example' => 'quantitative', 'help' => implode(', ', self::TYPES)],
            ['name' => 'unit', 'required' => false, 'example' => 'g/dL'],
            ['name' => 'fasting_required', 'required' => false, 'example' => 'no'],
            ['name' => 'turnaround_time_minutes', 'required' => false, 'example' => '120'],
            ['name' => 'requires_pathologist_approval', 'required' => false, 'example' => 'no'],
        ];
    }

    public function validate(array $raw, ImportContext $context): array
    {
        $errors = [];
        $tat = $this->number($raw, 'turnaround_time_minutes', $errors, false, 0);

        return ['errors' => $errors, 'data' => [
            'code' => $this->required($raw, 'code', $errors, 50),
            'name' => $this->required($raw, 'name', $errors),
            'short_name' => $this->text($raw, 'short_name'),
            'test_type' => $this->oneOf($raw, 'test_type', self::TYPES, $errors, 'quantitative'),
            'unit' => $this->text($raw, 'unit'),
            'fasting_required' => $this->boolean($raw, 'fasting_required'),
            'turnaround_time_minutes' => $tat !== null ? (int) $tat : null,
            'requires_pathologist_approval' => $this->boolean($raw, 'requires_pathologist_approval'),
        ]];
    }

    public function fileKey(array $data): ?string
    {
        return strtoupper($data['code']);
    }

    public function findExisting(array $data, ImportContext $context): ?string
    {
        $hit = LabTest::query()->where('organization_id', $context->organizationId)->where('code', $data['code'])->first();

        return $hit ? "lab test {$hit->code} ({$hit->name})" : null;
    }

    public function commit(array $data, ImportContext $context): array
    {
        $test = LabTest::create($data + ['organization_id' => $context->organizationId, 'branch_id' => null, 'is_active' => true]);

        return ['type' => LabTest::class, 'id' => $test->id];
    }

    public function rollback(string $type, int $id, ImportContext $context): string
    {
        return $this->removeOrDeactivate(LabTest::class, $id, $context->organizationId, fn ($t) => DB::table('lab_order_items')->where('test_id', $t->id)->exists());
    }
}
