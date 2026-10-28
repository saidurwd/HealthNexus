<?php

namespace App\Services\Portal;

use App\Models\Billing\BillingPayment;
use App\Models\Billing\BillingReceipt;
use App\Models\Patient;
use App\Models\PatientPortalAccount;
use App\Models\Portal\PortalDocument;
use App\Models\Portal\PortalResultRelease;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Secure delivery of a document (S07): a random link that is kept only as a hash, expires, can be limited to a few downloads, can be revoked, and is audited on every use.
 * The link alone is not enough — the person must also be signed in and allowed to open that patient's documents.
 */
class DocumentShareService
{
    public function __construct(private readonly PortalPolicy $policy, private readonly PortalAudit $audit, private readonly PortalRecordsService $records) {}

    /** @return array{0:PortalDocument,1:string} the row and the one-time plain token */
    public function share(int $patientId, string $sourceType, int $sourceId, ?PatientPortalAccount $account = null, ?User $user = null): array
    {
        $title = $this->assertShareable($patientId, $sourceType, $sourceId);
        $plain = Str::random(48);
        $doc = PortalDocument::create([
            'organization_id' => Patient::query()->findOrFail($patientId)->organization_id, 'patient_id' => $patientId, 'title' => $title, 'source_type' => $sourceType, 'source_id' => $sourceId,
            'token_hash' => hash('sha256', $plain), 'expires_at' => now()->addDays($this->policy->int('documents.link_ttl_days', 7)), 'max_downloads' => $this->policy->int('documents.max_downloads', 5),
            'created_by_account_id' => $account?->id, 'created_by' => $user?->id,
        ]);
        if ($account) {
            $this->audit->access($account, $patientId, 'share_link', $doc);
        }

        return [$doc, $plain];
    }

    /** Opens a link for a signed-in person. Counts and records the download. */
    public function open(PatientPortalAccount $account, string $plain, PortalAccess $access): array
    {
        $doc = PortalDocument::query()->where('token_hash', hash('sha256', $plain))->first();
        if ($doc === null || $doc->revoked_at !== null || $doc->expires_at->isPast() || ($doc->max_downloads !== null && $doc->downloads >= $doc->max_downloads)) {
            abort(410, __('This link has expired.'));
        }
        $patient = $access->patientFor($account, Patient::query()->findOrFail($doc->patient_id)->public_id, 'documents');
        $this->assertShareable($patient->id, $doc->source_type, $doc->source_id); // still released / still issued
        $doc->increment('downloads');
        $this->audit->access($account, $patient->id, 'download_document', $doc);

        return [$doc, $this->content($doc)];
    }

    public function revoke(PortalDocument $doc): void
    {
        $doc->update(['revoked_at' => now()]);
    }

    /** @return array<string, mixed> printable content */
    public function content(PortalDocument $doc): array
    {
        return match ($doc->source_type) {
            'lab_report', 'radiology_report' => ['type' => $doc->source_type, 'data' => $this->records->view(PortalResultRelease::query()->where(['source_type' => $doc->source_type, 'source_id' => $doc->source_id])->firstOrFail())],
            'receipt' => ['type' => 'receipt', 'data' => BillingReceipt::query()->with(['payment', 'invoice'])->findOrFail($doc->source_id)],
            'prescription' => ['type' => 'prescription', 'data' => Prescription::query()->with(['items', 'doctor:id,name'])->findOrFail($doc->source_id)],
            default => abort(404),
        };
    }

    private function assertShareable(int $patientId, string $type, int $id): string
    {
        switch ($type) {
            case 'lab_report':
            case 'radiology_report':
                $row = PortalResultRelease::query()->where(['patient_id' => $patientId, 'source_type' => $type, 'source_id' => $id, 'status' => 'released'])->first();
                abort_if($row === null, 404);

                return (string) $row->title;
            case 'receipt':
                $receipt = BillingReceipt::query()->find($id);
                abort_if($receipt === null || BillingPayment::query()->whereKey($receipt->payment_id)->value('patient_id') !== $patientId || $receipt->status === 'voided', 404);

                return __('Receipt :no', ['no' => $receipt->receipt_number]);
            case 'prescription':
                $rx = Prescription::query()->where('patient_id', $patientId)->whereNotNull('issued_at')->find($id);
                abort_if($rx === null, 404);

                return __('Prescription :no', ['no' => $rx->prescription_no]);
        }
        abort(404);
    }
}
