<?php

namespace App\Services\Ai;

use App\Models\Ai\AiFeedback;
use App\Models\Ai\AiOutput;
use App\Models\Patient;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

/**
 * An AI output is a draft until a person with the right accepts it. Acceptance confirms the patient (the number is typed, so a draft cannot be accepted against the wrong chart), may carry the reviewer's own
 * edited text (the generated text is never changed), and is recorded. Accepting does not write anything anywhere: the reviewer copies what they want into the record themselves.
 */
class OutputService
{
    public function __construct(private readonly AuditLogger $audit, private readonly AiIncidents $incidents) {}

    public function accept(AiOutput $o, User $by, ?string $confirmPatientNo = null, ?string $editedText = null, ?string $note = null): AiOutput
    {
        $this->reviewable($o, $by);
        if ($o->patient_id) {
            $patient = Patient::query()->where('organization_id', $o->organization_id)->find($o->patient_id);
            if (! $patient || trim((string) $confirmPatientNo) === '' || strcasecmp(trim((string) $confirmPatientNo), (string) $patient->enterprise_patient_no) !== 0) {
                $this->incidents->open($o->organization_id, 'wrong_patient', 'medium', $o->use_case_code, $o->id, __('An AI draft was about to be accepted with a patient number that is not the draft\'s patient.'), $by);
                throw ValidationException::withMessages(['patient_no' => __('The patient number does not match the draft. Check you are reading the right chart.')]);
            }
            if (! $by->can('patients.view')) {
                abort(403);
            }
        }
        $o->update(['status' => 'accepted', 'reviewed_by' => $by->id, 'reviewed_at' => now(), 'review_note' => $note ? mb_substr($note, 0, 500) : null, 'edited_text' => $editedText !== null && trim($editedText) !== '' && trim($editedText) !== trim($o->text) ? $editedText : null]);
        $this->audit->log('UPDATE', AiOutput::class, $o->id, ['status' => 'draft'], ['status' => 'accepted', 'edited' => $o->edited_text !== null]);

        return $o->refresh();
    }

    public function reject(AiOutput $o, User $by, string $reason): AiOutput
    {
        $this->reviewable($o, $by);
        if (mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages(['reason' => __('Say why it is rejected.')]);
        }
        $o->update(['status' => 'rejected', 'reviewed_by' => $by->id, 'reviewed_at' => now(), 'review_note' => mb_substr($reason, 0, 500)]);
        $this->audit->log('UPDATE', AiOutput::class, $o->id, ['status' => 'draft'], ['status' => 'rejected']);

        return $o->refresh();
    }

    /** Drafts nobody reviewed in time lapse. @return int */
    public function expireDue(int $organizationId): int
    {
        return AiOutput::query()->where('organization_id', $organizationId)->where('status', 'draft')->where('expires_at', '<', now())->update(['status' => 'expired']);
    }

    public function feedback(AiOutput $o, User $by, string $rating, ?string $errorType = null, ?string $comment = null): AiFeedback
    {
        if (! in_array($rating, ['useful', 'not_useful'], true) || ($errorType !== null && ! in_array($errorType, ['wrong_fact', 'missing', 'wrong_patient', 'unsafe', 'other'], true))) {
            throw ValidationException::withMessages(['rating' => __('Choose a rating.')]);
        }
        $f = AiFeedback::create(['organization_id' => $o->organization_id, 'output_id' => $o->id, 'user_id' => $by->id, 'rating' => $rating, 'error_type' => $errorType, 'comment' => $comment ? mb_substr($comment, 0, 500) : null]);
        if (in_array($errorType, ['wrong_patient', 'unsafe'], true)) {
            $this->incidents->open($o->organization_id, $errorType === 'unsafe' ? 'unsafe_output' : 'wrong_patient', 'high', $o->use_case_code, $o->id, __('A user reported an AI output as :t: :c', ['t' => str_replace('_', ' ', $errorType), 'c' => (string) $comment]), $by);
        }

        return $f;
    }

    /** What a clinician has accepted for a patient (to read and copy; nothing is written into the record). @return \Illuminate\Support\Collection<int,AiOutput> */
    public function acceptedFor(int $organizationId, int $patientId)
    {
        return AiOutput::query()->where(['organization_id' => $organizationId, 'patient_id' => $patientId, 'status' => 'accepted'])->orderByDesc('reviewed_at')->get();
    }

    private function reviewable(AiOutput $o, User $by): void
    {
        if (! $by->can('ai.output.review') || ! $by->organizations()->where('organizations.id', $o->organization_id)->exists()) {
            abort(403);
        }
        if ($o->status !== 'draft') {
            throw ValidationException::withMessages(['status' => __('This draft has already been dealt with.')]);
        }
        if ($o->expires_at->lt(now())) {
            $o->update(['status' => 'expired']);
            throw ValidationException::withMessages(['status' => __('This draft has expired: ask again.')]);
        }
        if ($o->kind === 'none') {
            throw ValidationException::withMessages(['status' => __('There is nothing in this answer to accept.')]);
        }
    }
}
