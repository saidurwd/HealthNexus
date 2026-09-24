<?php

namespace App\Services\Nursing;

use App\Models\Nursing\NursingEpisode;
use App\Models\Nursing\NursingNote;
use App\Models\Nursing\NursingNoteAmendment;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Once status=final, direct edits are refused — corrections only via addAddendum(), which never
 * touches the original content column (spec §40/§62).
 */
class NursingNoteService
{
    public function create(NursingEpisode $episode, array $data, User $user): NursingNote
    {
        return NursingNote::create([
            ...$data,
            'company_id' => $episode->company_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'admission_id' => $episode->admission_id,
            'patient_id' => $episode->patient_id,
            'status' => NursingNote::STATUS_DRAFT,
            'created_by' => $user->id,
        ]);
    }

    public function update(NursingNote $note, array $data): NursingNote
    {
        if ($note->status === NursingNote::STATUS_FINAL) {
            throw ValidationException::withMessages(['note' => 'This note is finalized — use an addendum to add further information.']);
        }

        $note->update($data);

        return $note->refresh();
    }

    public function finalize(NursingNote $note, User $user): NursingNote
    {
        if ($note->status === NursingNote::STATUS_FINAL) {
            throw ValidationException::withMessages(['note' => 'This note is already finalized.']);
        }

        $note->update(['status' => NursingNote::STATUS_FINAL, 'signed_at' => now(), 'signed_by' => $user->id]);

        return $note->refresh();
    }

    public function addAddendum(NursingNote $note, string $content, User $user): NursingNoteAmendment
    {
        if ($note->status !== NursingNote::STATUS_FINAL) {
            throw ValidationException::withMessages(['note' => 'Only a finalized note can receive an addendum — edit the draft directly instead.']);
        }

        return $note->amendments()->create([
            'amendment_type' => NursingNoteAmendment::TYPE_ADDENDUM,
            'reason' => 'Addendum',
            'content' => $content,
            'created_by' => $user->id,
        ]);
    }
}
