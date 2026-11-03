<?php

namespace App\Services\Portal;

use App\Models\Patient;
use App\Models\PatientPortalAccount;
use App\Models\Portal\PortalNotification;
use App\Models\Portal\PortalRefillRequest;
use App\Models\Prescription;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

/** A refill request is only ever a request: a person on staff reads it and decides; the portal never issues or dispenses anything (S05). */
class RefillService
{
    public function __construct(private readonly PortalPolicy $policy, private readonly AuditLogger $audit) {}

    public function request(PatientPortalAccount $account, Patient $patient, Prescription $prescription, ?string $note, string $idempotencyKey): PortalRefillRequest
    {
        $key = hash('sha256', $account->id.'|'.$idempotencyKey);
        if ($existing = PortalRefillRequest::query()->where('idempotency_key', $key)->first()) {
            return $existing;
        }
        if ($prescription->patient_id !== $patient->id || $prescription->issued_at === null) {
            throw new PortalException(__('That prescription is not available.'), 404);
        }
        if ($prescription->issued_at->lt(now()->subDays($this->policy->int('refill.window_days', 90)))) {
            throw new PortalException(__('This prescription is too old to refill online. Please book a visit.'), 409);
        }

        return DB::transaction(function () use ($account, $patient, $prescription, $note, $key) {
            $open = PortalRefillRequest::query()->where('prescription_id', $prescription->id)->whereIn('status', ['requested', 'approved'])->where('created_at', '>', now()->subDays($this->policy->int('refill.min_days_between', 7)))->lockForUpdate()->exists();
            if ($open) {
                throw new PortalException(__('A refill for this prescription was already asked for recently.'), 409);
            }
            $row = PortalRefillRequest::create(['organization_id' => $patient->organization_id, 'patient_id' => $patient->id, 'account_id' => $account->id, 'prescription_id' => $prescription->id, 'note' => $note ? mb_substr($note, 0, 300) : null, 'status' => 'requested', 'idempotency_key' => $key]);
            $this->audit->log('CREATE', PortalRefillRequest::class, $row->id, null, ['prescription_id' => $prescription->id]);

            return $row;
        });
    }

    public function decide(PortalRefillRequest $row, User $by, string $decision, ?string $note): PortalRefillRequest
    {
        if (! in_array($decision, ['approved', 'declined', 'needs_visit'], true)) {
            throw new PortalException(__('Unknown decision.'));
        }
        if ($row->status !== 'requested') {
            throw new PortalException(__('This request has already been decided.'), 409);
        }
        $row->update(['status' => $decision, 'reviewed_by' => $by->id, 'reviewed_at' => now(), 'decision_note' => $note ? mb_substr($note, 0, 300) : null]);
        $this->audit->log('UPDATE', PortalRefillRequest::class, $row->id, ['status' => 'requested'], ['status' => $decision, 'by' => $by->id]);
        $text = match ($decision) {
            'approved' => __('Your refill was approved. Please collect it from the pharmacy.'),
            'needs_visit' => __('The doctor would like to see you before refilling. Please book a visit.'),
            default => __('Your refill request could not be approved.'),
        };
        PortalNotification::create(['organization_id' => $row->organization_id, 'patient_id' => $row->patient_id, 'kind' => 'prescription', 'title' => __('Refill request answered'), 'body' => $text.($note ? ' '.$note : ''), 'link' => '/portal/prescriptions']);

        return $row;
    }

    public function cancel(PortalRefillRequest $row): PortalRefillRequest
    {
        if ($row->status === 'requested') {
            $row->update(['status' => 'cancelled']);
        }

        return $row;
    }
}
