<?php

namespace App\Services\Ot;

use App\Models\Ot\OtOperativeNote;
use App\Models\Ot\OtOperativeNoteAmendment;
use App\Models\Ot\OtSurgery;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Row-level lock-after-finalize, corrections via a sibling amendments table — mirrors
 * NursingNoteService exactly: addendum only, never a silent edit of a finalized note.
 */
class OtOperativeNoteService
{
    public function create(OtSurgery $surgery, array $data, User $user): OtOperativeNote
    {
        return OtOperativeNote::updateOrCreate(
            ['surgery_id' => $surgery->id],
            [...$data, 'status' => OtOperativeNote::STATUS_DRAFT, 'documented_by' => $user->id],
        );
    }

    public function update(OtOperativeNote $note, array $data): OtOperativeNote
    {
        if ($note->status === OtOperativeNote::STATUS_FINAL) {
            throw ValidationException::withMessages(['note' => 'This operative note is already finalized and cannot be edited directly.']);
        }

        $note->update($data);

        return $note->refresh();
    }

    public function finalize(OtOperativeNote $note, User $user): OtOperativeNote
    {
        if ($note->status === OtOperativeNote::STATUS_FINAL) {
            throw ValidationException::withMessages(['note' => 'This operative note is already finalized.']);
        }

        $note->update(['status' => OtOperativeNote::STATUS_FINAL, 'finalized_at' => now(), 'finalized_by' => $user->id]);

        return $note->refresh();
    }

    public function addAddendum(OtOperativeNote $note, string $content, string $reason, User $user): OtOperativeNoteAmendment
    {
        if ($note->status !== OtOperativeNote::STATUS_FINAL) {
            throw ValidationException::withMessages(['note' => 'Only a finalized operative note can receive an addendum — edit the draft directly instead.']);
        }

        return $note->amendments()->create([
            'amendment_type' => 'addendum',
            'reason' => $reason,
            'content' => $content,
            'created_by' => $user->id,
        ]);
    }
}
