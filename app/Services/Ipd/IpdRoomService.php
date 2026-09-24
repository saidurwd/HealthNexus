<?php

namespace App\Services\Ipd;

use App\Models\Ipd\IpdRoom;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CRUD for rooms within a ward. Soft-delete only — beds reference rooms historically.
 */
class IpdRoomService
{
    public function create(array $data, User $user): IpdRoom
    {
        return DB::transaction(function () use ($data) {
            $this->assertUniqueNumber($data['ward_id'], $data['room_number']);

            return IpdRoom::create($data);
        });
    }

    public function update(IpdRoom $room, array $data, User $user): IpdRoom
    {
        return DB::transaction(function () use ($room, $data) {
            if (isset($data['room_number']) && $data['room_number'] !== $room->room_number) {
                $this->assertUniqueNumber($room->ward_id, $data['room_number'], $room->id);
            }

            $room->update($data);

            return $room->refresh();
        });
    }

    public function deactivate(IpdRoom $room, User $user): void
    {
        $room->update(['is_active' => false]);
        $room->delete();
    }

    private function assertUniqueNumber(int $wardId, string $roomNumber, ?int $exceptId = null): void
    {
        $exists = IpdRoom::query()
            ->where('ward_id', $wardId)
            ->where('room_number', $roomNumber)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['room_number' => 'This room number is already in use for this ward.']);
        }
    }
}
