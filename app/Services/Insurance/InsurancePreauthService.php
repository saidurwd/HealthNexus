<?php

namespace App\Services\Insurance;

use App\Models\Billing\BillingInsurancePolicy;
use App\Models\Encounter;
use App\Models\Insurance\InsurancePreauth;
use App\Models\Patient;
use App\Models\User;
use App\Services\Numbering\NumberSeriesService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Pre-authorization: the amount a payer promises, how much of it has been used, and when it lapses. The authorized amount is a hard cap. */
class InsurancePreauthService
{
    public function __construct(private readonly NumberSeriesService $numbers, private readonly InsurancePolicySettings $settings) {}

    public function request(BillingInsurancePolicy $policy, Patient $patient, User $by, string $description, string $amount, ?Encounter $encounter = null): InsurancePreauth
    {
        if (bccomp($amount, '0', 2) <= 0) {
            throw ValidationException::withMessages(['requested_amount' => 'Ask for an amount above zero.']);
        }
        if (! $policy->payer_id) {
            throw ValidationException::withMessages(['policy' => 'This policy has no payer on the register.']);
        }

        return InsurancePreauth::create([
            'organization_id' => $policy->organization_id, 'policy_id' => $policy->id, 'payer_id' => $policy->payer_id, 'patient_id' => $patient->id, 'encounter_id' => $encounter?->id,
            'request_number' => $this->numbers->next('insurance.preauth', $policy->organization_id, null, prefix: 'PRE', format: '{PREFIX}-{YEAR}-{SEQ:6}', userId: $by->id),
            'service_description' => $description, 'requested_amount' => $amount, 'requested_at' => now(), 'requested_by' => $by->id,
        ]);
    }

    public function decide(InsurancePreauth $preauth, string $status, string $authorizedAmount, ?string $payerReference, ?string $validFrom = null, ?string $validTo = null, ?string $note = null): InsurancePreauth
    {
        return DB::transaction(function () use ($preauth, $status, $authorizedAmount, $payerReference, $validFrom, $validTo, $note) {
            $locked = InsurancePreauth::query()->lockForUpdate()->findOrFail($preauth->id);
            if ($locked->status !== 'requested') {
                throw ValidationException::withMessages(['preauth' => 'This request has already been decided.']);
            }
            if (! in_array($status, ['approved', 'partial', 'rejected'], true)) {
                throw ValidationException::withMessages(['status' => 'Not a known decision.']);
            }
            if ($status === 'rejected') {
                $authorizedAmount = '0.00';
            } elseif (bccomp($authorizedAmount, '0', 2) <= 0 || ($status === 'approved' && bccomp($authorizedAmount, (string) $locked->requested_amount, 2) < 0) || ($status === 'partial' && bccomp($authorizedAmount, (string) $locked->requested_amount, 2) >= 0)) {
                throw ValidationException::withMessages(['authorized_amount' => 'The amount does not fit the decision (approved = all asked, partial = less than asked).']);
            }
            $from = $validFrom ?? now()->toDateString();
            $locked->update([
                'status' => $status, 'authorized_amount' => $authorizedAmount, 'payer_reference' => $payerReference, 'decision_note' => $note, 'decided_at' => now(),
                'valid_from' => $from, 'valid_to' => $validTo ?? date('Y-m-d', strtotime("$from +".(int) $this->settings->get('preauth')['valid_days'].' days')),
            ]);

            return $locked->refresh();
        });
    }

    /** The authorization that can still be used for this policy on a date, if any. */
    public function usable(BillingInsurancePolicy $policy, string $date, ?Encounter $encounter = null): ?InsurancePreauth
    {
        return InsurancePreauth::query()->where('policy_id', $policy->id)->whereIn('status', ['approved', 'partial'])->whereDate('valid_from', '<=', $date)->whereDate('valid_to', '>=', $date)
            ->when($encounter, fn ($q) => $q->where(fn ($w) => $w->whereNull('encounter_id')->orWhere('encounter_id', $encounter->id)))
            ->whereColumn('consumed_amount', '<', 'authorized_amount')->orderBy('valid_to')->first();
    }

    /** Uses part of an authorization. Never above what is left, even when two claims race. */
    public function consume(InsurancePreauth $preauth, string $amount): InsurancePreauth
    {
        return DB::transaction(function () use ($preauth, $amount) {
            $locked = InsurancePreauth::query()->lockForUpdate()->findOrFail($preauth->id);
            if (! in_array($locked->status, ['approved', 'partial'], true)) {
                throw ValidationException::withMessages(['preauth' => 'This authorization cannot be used.']);
            }
            if (bccomp($amount, $locked->remaining(), 2) > 0) {
                throw ValidationException::withMessages(['preauth' => "Only {$locked->remaining()} of the authorization is left."]);
            }
            $locked->update(['consumed_amount' => bcadd((string) $locked->consumed_amount, $amount, 2)]);

            return $locked->refresh();
        });
    }

    public function release(InsurancePreauth $preauth, string $amount): void
    {
        DB::transaction(function () use ($preauth, $amount) {
            $locked = InsurancePreauth::query()->lockForUpdate()->findOrFail($preauth->id);
            $left = bcsub((string) $locked->consumed_amount, $amount, 2);
            $locked->update(['consumed_amount' => bccomp($left, '0', 2) < 0 ? '0.00' : $left]);
        });
    }

    /** Authorizations past their last day lapse. @return int lapsed */
    public function expireDue(): int
    {
        return InsurancePreauth::query()->whereIn('status', ['approved', 'partial'])->whereDate('valid_to', '<', now()->toDateString())->update(['status' => 'expired']);
    }
}
