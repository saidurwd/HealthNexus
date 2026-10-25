<?php

namespace App\Services\Icu;

use App\Events\Icu\ICUProgressNoteFinalized;
use App\Models\Icu\IcuEpisode;
use App\Models\Icu\IcuProgressNote;
use App\Models\Icu\IcuProgressNoteAmendment;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Mirrors NursingNoteService/OtOperativeNoteService exactly — draft->final lock, addendum-only
 * correction. "Physician rounds" (spec §13) uses this same service with note_type='rounds'; no
 * separate icu_rounds table (Phase 11 plan decision #7).
 */
class IcuProgressNoteService
{
    public function create(IcuEpisode $episode, array $data, User $user): IcuProgressNote
    {
        return IcuProgressNote::create([
            ...$data,
            'company_id' => $episode->company_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'patient_id' => $episode->patient_id,
            'note_type' => $data['note_type'] ?? IcuProgressNote::TYPE_PROGRESS,
            'status' => IcuProgressNote::STATUS_DRAFT,
            'created_by' => $user->id,
        ]);
    }

    public function update(IcuProgressNote $note, array $data): IcuProgressNote
    {
        if ($note->status === IcuProgressNote::STATUS_FINAL) {
            throw ValidationException::withMessages(['note' => 'This note is finalized — use an addendum to add further information.']);
        }

        $note->update($data);

        return $note->refresh();
    }

    public function finalize(IcuProgressNote $note, User $user): IcuProgressNote
    {
        if ($note->status === IcuProgressNote::STATUS_FINAL) {
            throw ValidationException::withMessages(['note' => 'This note is already finalized.']);
        }

        $note->update(['status' => IcuProgressNote::STATUS_FINAL, 'signed_at' => now(), 'signed_by' => $user->id]);

        event(new ICUProgressNoteFinalized($note));

        return $note->refresh();
    }

    public function addAddendum(IcuProgressNote $note, string $content, User $user): IcuProgressNoteAmendment
    {
        if ($note->status !== IcuProgressNote::STATUS_FINAL) {
            throw ValidationException::withMessages(['note' => 'Only a finalized note can receive an addendum — edit the draft directly instead.']);
        }

        return $note->amendments()->create([
            'amendment_type' => IcuProgressNoteAmendment::TYPE_ADDENDUM,
            'reason' => 'Addendum',
            'content' => $content,
            'created_by' => $user->id,
        ]);
    }
}
