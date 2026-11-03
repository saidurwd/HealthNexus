<?php

namespace App\Services\Import\Definitions;

use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdRoom;
use App\Models\Ipd\IpdWard;
use App\Services\Import\ImportContext;
use Illuminate\Support\Facades\DB;

class BedImport extends BaseImport
{
    public function key(): string
    {
        return 'beds';
    }

    public function title(): string
    {
        return 'Beds';
    }

    public function permission(): string
    {
        return 'import.beds';
    }

    public function columns(): array
    {
        return [
            ['name' => 'ward_code', 'required' => true, 'example' => 'MED', 'help' => 'The ward must already exist'],
            ['name' => 'room_number', 'required' => true, 'example' => '101', 'help' => 'The room must already exist in that ward'],
            ['name' => 'bed_code', 'required' => true, 'example' => 'MED-101-A'],
            ['name' => 'bed_name', 'required' => false, 'example' => 'Bed A'],
            ['name' => 'gender_type', 'required' => false, 'example' => 'any', 'help' => 'any, male or female'],
            ['name' => 'isolation_capable', 'required' => false, 'example' => 'no'],
            ['name' => 'oxygen_available', 'required' => false, 'example' => 'yes'],
            ['name' => 'monitor_available', 'required' => false, 'example' => 'no'],
        ];
    }

    public function validate(array $raw, ImportContext $context): array
    {
        $errors = [];
        $wardCode = $this->required($raw, 'ward_code', $errors, 50);
        $roomNumber = $this->required($raw, 'room_number', $errors, 50);
        $room = null;

        if ($wardCode && $roomNumber) {
            $ward = IpdWard::query()->where('organization_id', $context->organizationId)->where('code', $wardCode)->first();
            if (! $ward) {
                $errors[] = "ward_code [$wardCode] does not exist. Create the ward first.";
            } else {
                $room = IpdRoom::query()->where('organization_id', $context->organizationId)->where('ward_id', $ward->id)->where('room_number', $roomNumber)->first();
                $room || $errors[] = "Room [$roomNumber] does not exist in ward [$wardCode]. Create the room first.";
            }
        }

        return ['errors' => $errors, 'data' => [
            'room_id' => $room?->id,
            'bed_code' => $this->required($raw, 'bed_code', $errors, 50),
            'bed_name' => $this->text($raw, 'bed_name'),
            'gender_type' => $this->oneOf($raw, 'gender_type', ['any', 'male', 'female'], $errors, 'any'),
            'isolation_capable' => $this->boolean($raw, 'isolation_capable'),
            'oxygen_available' => $this->boolean($raw, 'oxygen_available'),
            'monitor_available' => $this->boolean($raw, 'monitor_available'),
        ]];
    }

    public function fileKey(array $data): ?string
    {
        return strtoupper($data['bed_code']);
    }

    public function findExisting(array $data, ImportContext $context): ?string
    {
        $hit = IpdBed::query()->where('organization_id', $context->organizationId)->where('bed_code', $data['bed_code'])->first();

        return $hit ? "bed {$hit->bed_code}" : null;
    }

    public function commit(array $data, ImportContext $context): array
    {
        $bed = IpdBed::create($data + ['organization_id' => $context->organizationId, 'branch_id' => null, 'status' => IpdBed::STATUS_AVAILABLE, 'is_active' => true]);

        return ['type' => IpdBed::class, 'id' => $bed->id];
    }

    public function rollback(string $type, int $id, ImportContext $context): string
    {
        return $this->removeOrDeactivate(IpdBed::class, $id, $context->organizationId, fn ($bed) => DB::table('ipd_bed_allocations')->where('bed_id', $bed->id)->exists());
    }
}
