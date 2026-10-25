<?php

namespace App\Services\Import\Definitions;

use App\Models\Pharmacy\PharmacyMedication;
use App\Services\Import\ImportContext;
use Illuminate\Support\Facades\DB;

class MedicationImport extends BaseImport
{
    public function key(): string
    {
        return 'medications';
    }

    public function title(): string
    {
        return 'Drugs (medication catalogue)';
    }

    public function permission(): string
    {
        return 'import.medications';
    }

    public function columns(): array
    {
        return [
            ['name' => 'code', 'required' => true, 'example' => 'NAPA-500'],
            ['name' => 'name', 'required' => true, 'example' => 'Napa 500 mg Tablet'],
            ['name' => 'strength', 'required' => false, 'example' => '500'],
            ['name' => 'strength_unit', 'required' => false, 'example' => 'mg'],
            ['name' => 'pack_size', 'required' => false, 'example' => '10'],
            ['name' => 'dispensing_unit', 'required' => false, 'example' => 'tablet'],
            ['name' => 'manufacturer', 'required' => false, 'example' => 'Beximco Pharmaceuticals'],
            ['name' => 'is_prescription_required', 'required' => false, 'example' => 'no'],
            ['name' => 'is_controlled', 'required' => false, 'example' => 'no'],
            ['name' => 'is_high_alert', 'required' => false, 'example' => 'no'],
        ];
    }

    public function validate(array $raw, ImportContext $context): array
    {
        $errors = [];
        $pack = $this->number($raw, 'pack_size', $errors, false, 1);

        return ['errors' => $errors, 'data' => [
            'code' => $this->required($raw, 'code', $errors, 50),
            'name' => $this->required($raw, 'name', $errors),
            'strength' => $this->text($raw, 'strength'),
            'strength_unit' => $this->text($raw, 'strength_unit'),
            'pack_size' => $pack !== null ? (int) $pack : null,
            'dispensing_unit' => $this->text($raw, 'dispensing_unit'),
            'manufacturer' => $this->text($raw, 'manufacturer'),
            'is_prescription_required' => $this->boolean($raw, 'is_prescription_required', true),
            'is_controlled' => $this->boolean($raw, 'is_controlled'),
            'is_high_alert' => $this->boolean($raw, 'is_high_alert'),
        ]];
    }

    public function fileKey(array $data): ?string
    {
        return strtoupper($data['code']);
    }

    public function findExisting(array $data, ImportContext $context): ?string
    {
        $hit = PharmacyMedication::query()->where('organization_id', $context->organizationId)->where('code', $data['code'])->first();

        return $hit ? "medication {$hit->code} ({$hit->name})" : null;
    }

    public function commit(array $data, ImportContext $context): array
    {
        $medication = PharmacyMedication::create($data + ['organization_id' => $context->organizationId, 'branch_id' => null, 'is_active' => true]);

        return ['type' => PharmacyMedication::class, 'id' => $medication->id];
    }

    public function rollback(string $type, int $id, ImportContext $context): string
    {
        return $this->removeOrDeactivate(PharmacyMedication::class, $id, $context->organizationId, fn ($m) => DB::table('pharmacy_stock')->where('medication_id', $m->id)->exists()
            || DB::table('pharmacy_order_items')->where('medication_id', $m->id)->exists());
    }
}
