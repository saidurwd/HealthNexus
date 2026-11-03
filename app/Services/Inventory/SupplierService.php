<?php

namespace App\Services\Inventory;

use App\Models\Inventory\ProcurementSupplier;
use App\Models\Inventory\ProcurementSupplierDocument;
use App\Models\Inventory\ProcurementSupplierEvaluation;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Clinical\Alerts\AlertRequest;
use App\Services\Clinical\Alerts\AlertService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Supplier register: qualification, documents with expiry, scorecard (S02). A supplier that is not qualified cannot be ordered from. */
class SupplierService
{
    public function __construct(private readonly InventoryPolicy $policy, private readonly AlertService $alerts, private readonly AuditLogger $audit) {}

    /** @param  array<string, mixed>  $data */
    public function create(int $organizationId, array $data): ProcurementSupplier
    {
        $code = strtoupper((string) $data['code']);
        if (ProcurementSupplier::query()->where('organization_id', $organizationId)->where('code', $code)->exists()) {
            throw ValidationException::withMessages(['code' => 'That supplier code is already used.']);
        }

        return ProcurementSupplier::create(['organization_id' => $organizationId, 'code' => $code, 'status' => 'prospective'] + $data);
    }

    public function addDocument(ProcurementSupplier $supplier, string $type, ?string $number, ?string $issuedOn, ?string $expiresOn, ?int $fileId = null): ProcurementSupplierDocument
    {
        if (! in_array($type, (array) $this->policy->get('supplier_document_types'), true)) {
            throw ValidationException::withMessages(['doc_type' => 'Not a known document type.']);
        }
        if ($issuedOn && $expiresOn && $expiresOn < $issuedOn) {
            throw ValidationException::withMessages(['expires_on' => 'The document expires before it was issued.']);
        }

        return $supplier->documents()->create(['organization_id' => $supplier->organization_id, 'doc_type' => $type, 'number' => $number, 'issued_on' => $issuedOn, 'expires_on' => $expiresOn, 'file_id' => $fileId]);
    }

    /** Required documents that are missing or out of date. @return list<string> */
    public function documentGaps(ProcurementSupplier $supplier, ?CarbonInterface $on = null): array
    {
        $on ??= now();
        $gaps = [];
        foreach ((array) $this->policy->get('supplier_required_documents') as $type) {
            $valid = $supplier->documents()->where('doc_type', $type)->where(fn ($q) => $q->whereNull('expires_on')->orWhereDate('expires_on', '>=', $on->toDateString()))->exists();
            $valid || $gaps[] = $type;
        }

        return $gaps;
    }

    public function qualify(ProcurementSupplier $supplier, User $by): ProcurementSupplier
    {
        if ($supplier->status === 'disqualified') {
            throw ValidationException::withMessages(['status' => 'A disqualified supplier cannot be qualified again; register a new supplier record if circumstances changed.']);
        }
        if ($gaps = $this->documentGaps($supplier)) {
            throw ValidationException::withMessages(['documents' => 'Missing or expired: '.implode(', ', $gaps).'.']);
        }
        $supplier->update(['status' => 'qualified', 'status_reason' => null, 'qualified_by' => $by->id, 'qualified_at' => now()]);
        $this->audit->log('SUPPLIER_QUALIFIED', ProcurementSupplier::class, $supplier->id, null, ['code' => $supplier->code]);

        return $supplier->refresh();
    }

    public function suspend(ProcurementSupplier $supplier, string $reason): ProcurementSupplier
    {
        $supplier->update(['status' => 'suspended', 'status_reason' => $reason]);

        return $supplier->refresh();
    }

    public function disqualify(ProcurementSupplier $supplier, string $reason, User $by): ProcurementSupplier
    {
        $supplier->update(['status' => 'disqualified', 'status_reason' => $reason]);
        $this->audit->log('SUPPLIER_DISQUALIFIED', ProcurementSupplier::class, $supplier->id, null, ['reason' => $reason, 'by' => $by->id]);

        return $supplier->refresh();
    }

    /** Throws unless an order may be placed with this supplier today. */
    public function assertCanOrder(ProcurementSupplier $supplier): void
    {
        if ($supplier->status !== 'qualified') {
            throw ValidationException::withMessages(['supplier' => "{$supplier->name} is {$supplier->status} and cannot be ordered from."]);
        }
        if ($gaps = $this->documentGaps($supplier)) {
            throw ValidationException::withMessages(['supplier' => "{$supplier->name} has a missing or expired document (".implode(', ', $gaps).').']);
        }
    }

    /** Scorecard (0–5 each). A total under the minimum suspends the supplier until someone qualifies it again. */
    public function evaluate(ProcurementSupplier $supplier, User $by, float $delivery, float $quality, float $price, ?string $notes = null, ?string $periodEnd = null): ProcurementSupplierEvaluation
    {
        foreach ([$delivery, $quality, $price] as $score) {
            if ($score < 0 || $score > 5) {
                throw ValidationException::withMessages(['score' => 'Scores run from 0 to 5.']);
            }
        }
        $w = (array) $this->policy->get('evaluation_weights');
        $total = round($delivery * $w['delivery'] + $quality * $w['quality'] + $price * $w['price'], 2);

        return DB::transaction(function () use ($supplier, $by, $delivery, $quality, $price, $notes, $periodEnd, $total) {
            $evaluation = $supplier->evaluations()->create([
                'organization_id' => $supplier->organization_id, 'period_end' => $periodEnd ?? now()->toDateString(), 'delivery_score' => $delivery, 'quality_score' => $quality,
                'price_score' => $price, 'total_score' => $total, 'notes' => $notes, 'evaluated_by' => $by->id,
            ]);
            $supplier->update(['rating' => $total]);
            if ($total < (float) $this->policy->get('supplier_min_score') && $supplier->status === 'qualified') {
                $this->suspend($supplier, "Scorecard {$total} is below the minimum.");
            }

            return $evaluation;
        });
    }

    /** Raises an alert for every document that expires within the alert windows (once per document and window). @return int alerts raised */
    public function raiseDocumentAlerts(int $organizationId, array $recipientUserIds = []): int
    {
        $raised = 0;
        $windows = (array) $this->policy->get('document_expiry_alert_days');
        $widest = $windows ? max($windows) : 0;
        $docs = ProcurementSupplierDocument::query()->where('organization_id', $organizationId)->whereNotNull('expires_on')->whereBetween('expires_on', [now()->toDateString(), now()->addDays($widest)->toDateString()])
            ->whereHas('supplier', fn ($q) => $q->whereIn('status', ['qualified', 'suspended']))->with('supplier')->get();
        foreach ($docs as $doc) {
            $days = (int) now()->startOfDay()->diffInDays($doc->expires_on, false);
            $window = collect($windows)->sort()->first(fn ($w) => $days <= $w);
            if ($window === null) {
                continue;
            }
            $alert = $this->alerts->raise(new AlertRequest(
                organizationId: $organizationId, category: 'supplier_document_expiry', severity: $window <= 7 ? 'critical' : 'warning', sourceModule: 'inventory',
                message: "{$doc->supplier->name}: {$doc->doc_type} expires in {$days} day(s).", sourceType: ProcurementSupplierDocument::class, sourceId: $doc->id,
                recipientUserIds: $recipientUserIds, dedupKey: "supplier-doc:{$doc->id}:{$window}",
            ));
            $alert->wasRecentlyCreated && $raised++;
        }

        return $raised;
    }
}
