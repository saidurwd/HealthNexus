<?php

namespace App\Services\Insurance;

use App\Contracts\Integration\PayerGateway;
use App\Models\Billing\BillingInsurancePolicy;
use App\Models\Insurance\InsuranceClaim;
use App\Models\Insurance\InsurancePayer;
use App\Models\Insurance\InsuranceSubmission;
use App\Models\User;
use App\Services\Numbering\NumberSeriesService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Sending claims. The first adapter is manual, because most local payers have no API: export the batch as a file, send it however the payer
 * wants, then mark it submitted with the payer's reference. A payer with an API goes through the PayerGateway instead. Either way the claim is
 * locked the moment it is submitted.
 */
class InsuranceSubmissionService
{
    public function __construct(
        private readonly NumberSeriesService $numbers,
        private readonly InsuranceClaimService $claims,
        private readonly PayerGateway $gateway,
    ) {}

    /** Writes the batch file for ready claims of one payer. The claims stay "ready" until they are marked submitted. @param  iterable<InsuranceClaim>  $claims */
    public function exportBatch(InsurancePayer $payer, iterable $claims, User $by): InsuranceSubmission
    {
        $claims = collect($claims)->values();
        if ($claims->isEmpty()) {
            throw ValidationException::withMessages(['claims' => 'Choose at least one claim.']);
        }
        foreach ($claims as $claim) {
            if ($claim->payer_id !== $payer->id || $claim->organization_id !== $payer->organization_id) {
                throw ValidationException::withMessages(['claims' => "Claim {$claim->claim_number} is for another payer."]);
            }
            if ($claim->status !== 'ready') {
                throw ValidationException::withMessages(['claims' => "Claim {$claim->claim_number} is {$claim->status}; only ready claims go in a batch."]);
            }
        }

        return DB::transaction(function () use ($payer, $claims, $by) {
            $batchNumber = $this->numbers->next('insurance.batch', $payer->organization_id, null, prefix: 'SUB', format: '{PREFIX}-{YEAR}-{SEQ:6}', userId: $by->id);
            $path = "insurance/exports/$batchNumber.csv";
            Storage::disk('local')->put($path, $this->csv($claims));

            $submission = InsuranceSubmission::create([
                'organization_id' => $payer->organization_id, 'payer_id' => $payer->id, 'batch_number' => $batchNumber, 'method' => $payer->submission_method, 'claim_count' => $claims->count(),
                'total_amount' => $claims->reduce(fn ($c, $claim) => bcadd($c, (string) $claim->payer_share, 2), '0.00'), 'file_path' => $path,
            ]);
            foreach ($claims as $claim) {
                DB::table('insurance_submission_claims')->insert(['submission_id' => $submission->id, 'claim_id' => $claim->id, 'amount' => $claim->payer_share, 'created_at' => now(), 'updated_at' => now()]);
                $this->claims->event($claim, 'exported', $by, "In batch $batchNumber");
            }

            return $submission;
        });
    }

    /** The file went to the payer: lock the claims and record the reference. */
    public function markSubmitted(InsuranceSubmission $submission, string $payerReference, User $by): InsuranceSubmission
    {
        return DB::transaction(function () use ($submission, $payerReference, $by) {
            $locked = InsuranceSubmission::query()->lockForUpdate()->findOrFail($submission->id);
            if ($locked->submitted_at !== null) {
                throw ValidationException::withMessages(['submission' => 'This batch was already marked as submitted.']);
            }
            if (blank($payerReference)) {
                throw ValidationException::withMessages(['payer_reference' => 'Record the reference the payer gave (or the e-mail / upload receipt).']);
            }
            $ids = DB::table('insurance_submission_claims')->where('submission_id', $locked->id)->pluck('claim_id');
            foreach (InsuranceClaim::whereIn('id', $ids)->get() as $claim) {
                $this->claims->transition($claim, 'submitted', $by, "Batch {$locked->batch_number}, reference $payerReference");
                $claim->update(['payer_reference' => $payerReference]);
            }
            $locked->update(['payer_reference' => $payerReference, 'submitted_at' => now(), 'submitted_by' => $by->id]);

            return $locked->refresh();
        });
    }

    /** For a payer with an API: send each claim through the gateway. A claim the gateway does not take stays ready. @return array{sent: int, kept: int} */
    public function submitViaGateway(InsurancePayer $payer, iterable $claims, User $by): array
    {
        $sent = $kept = 0;
        foreach ($claims as $claim) {
            if ($claim->status !== 'ready' || $claim->payer_id !== $payer->id) {
                $kept++;

                continue;
            }
            $result = $this->gateway->submitClaim(['claim_number' => $claim->claim_number, 'payer_code' => $payer->code, 'amount' => (string) $claim->payer_share]);
            if ($result->handled) {
                $this->claims->transition($claim, 'submitted', $by, 'Sent through the payer interface');
                $claim->update(['payer_reference' => $result->data['reference'] ?? null]);
                $sent++;
            } else {
                $this->claims->event($claim, 'submission_not_sent', $by, $result->reason);
                $kept++;
            }
        }

        return ['sent' => $sent, 'kept' => $kept];
    }

    /** @param  Collection<int, InsuranceClaim>  $claims */
    private function csv($claims): string
    {
        $out = fopen('php://memory', 'w+');
        fputcsv($out, ['claim_number', 'version', 'claim_type', 'service_date', 'member_number', 'policy_number', 'patient', 'diagnosis_codes', 'line', 'item_code', 'description', 'quantity', 'gross', 'contract_amount', 'payer_share', 'patient_share']);
        foreach ($claims as $claim) {
            $policy = $claim->policy_id ? BillingInsurancePolicy::find($claim->policy_id) : null;
            $codes = collect($claim->diagnoses)->pluck('code')->filter()->implode(';');
            foreach ($claim->lines()->orderBy('id')->get() as $i => $line) {
                fputcsv($out, [$claim->claim_number, $claim->version, $claim->claim_type, $claim->service_date->toDateString(), $policy?->member_number, $policy?->policy_number, $claim->patient->full_name, $codes, $i + 1, $line->item_code, $line->description, $line->quantity, $line->gross_amount, $line->contract_amount, $line->payer_share, $line->patient_share]);
            }
        }
        rewind($out);

        return "\xEF\xBB\xBF".stream_get_contents($out);
    }
}
