<?php

namespace App\Services\Facility;

use App\Models\Facility\FacAsset;
use App\Models\Facility\FacCalibration;
use App\Models\Facility\FacMaintenancePlan;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Numbering\NumberSeriesService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * The equipment register. Money and depreciation stay in Finance (this keeps only a link). `usable()` is what other modules ask before putting a device to work: it is false when the
 * asset is out of service or retired, or when a high-risk asset's calibration is overdue or failed.
 */
class AssetService
{
    public function __construct(private readonly NumberSeriesService $numbers, private readonly FacilityPolicy $policy, private readonly AuditLogger $audit) {}

    /** @param array<string,mixed> $d name, category, tag, manufacturer, model, serial_no, supplier_name, department_id, location, purchased_on, cost, warranty_until, risk_class, branch_id, note */
    public function register(int $organizationId, array $d, ?User $by = null): FacAsset
    {
        if (mb_strlen(trim((string) ($d['name'] ?? ''))) < 2) {
            throw ValidationException::withMessages(['name' => __('Give the equipment a name.')]);
        }
        if (! in_array($d['category'] ?? '', $this->policy->list('asset.categories'), true)) {
            throw ValidationException::withMessages(['category' => __('Choose the kind of equipment.')]);
        }
        $risk = $d['risk_class'] ?? 'medium';
        if (! in_array($risk, $this->policy->list('asset.risk_classes'), true)) {
            throw ValidationException::withMessages(['risk_class' => __('Choose the risk class.')]);
        }
        $tag = strtoupper(trim((string) ($d['tag'] ?? '')));
        if ($tag === '') {
            $tag = $this->numbers->next('fac.asset', $organizationId, null, prefix: 'EQ', format: '{PREFIX}-{SEQ:5}', period: NumberSeriesService::PERIOD_NONE);
        } elseif (FacAsset::query()->where(['organization_id' => $organizationId, 'tag' => $tag])->exists()) {
            throw ValidationException::withMessages(['tag' => __('That tag is already used.')]);
        }
        $asset = FacAsset::create(['organization_id' => $organizationId, 'branch_id' => $d['branch_id'] ?? null, 'tag' => $tag, 'name' => mb_substr(trim($d['name']), 0, 150), 'category' => $d['category'], 'manufacturer' => $this->clip($d['manufacturer'] ?? null, 100),
            'model' => $this->clip($d['model'] ?? null, 100), 'serial_no' => $this->clip($d['serial_no'] ?? null, 80), 'supplier_name' => $this->clip($d['supplier_name'] ?? null, 150), 'department_id' => $d['department_id'] ?? null,
            'location' => $this->clip($d['location'] ?? null, 120), 'purchased_on' => $d['purchased_on'] ?? null, 'cost' => $d['cost'] ?? null, 'warranty_until' => $d['warranty_until'] ?? null, 'risk_class' => $risk, 'status' => 'in_service',
            'qr_token' => bin2hex(random_bytes(16)), 'note' => $this->clip($d['note'] ?? null, 500)]);
        $this->audit->log('CREATE', FacAsset::class, $asset->id, null, ['tag' => $tag]);

        return $asset;
    }

    public function move(FacAsset $asset, ?string $location, ?int $departmentId): FacAsset
    {
        $asset->update(['location' => $this->clip($location, 120), 'department_id' => $departmentId]);

        return $asset;
    }

    public function retire(FacAsset $asset, User $by, string $reason): FacAsset
    {
        if ($asset->status === 'retired') {
            throw ValidationException::withMessages(['status' => __('Already retired.')]);
        }
        if (mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages(['reason' => __('Say why it is retired.')]);
        }
        $asset->update(['status' => 'retired', 'retired_on' => today()->toDateString(), 'note' => mb_substr($reason, 0, 500)]);
        FacMaintenancePlan::query()->where('asset_id', $asset->id)->update(['active' => false]);
        $this->audit->log('UPDATE', FacAsset::class, $asset->id, null, ['status' => 'retired']);

        return $asset;
    }

    /** Link to Finance's fixed-asset record (by id); depreciation is not copied. */
    public function linkFinance(FacAsset $asset, ?int $financeAssetId): FacAsset
    {
        $asset->update(['fin_asset_id' => $financeAssetId]);

        return $asset;
    }

    /** May this equipment be used today? */
    public function usable(FacAsset $asset): bool
    {
        return $this->reason($asset) === null;
    }

    /** Why it may not be used, or null. */
    public function reason(FacAsset $asset): ?string
    {
        if (in_array($asset->status, ['out_of_service', 'retired'], true)) {
            return $asset->status === 'retired' ? __('Retired') : __('Out of service');
        }
        if ($asset->status === 'under_maintenance') {
            return __('Under maintenance');
        }
        if ($asset->risk_class === 'high' && $this->policy->bool('asset.block_on_overdue_calibration', true)) {
            $last = FacCalibration::query()->where('asset_id', $asset->id)->orderByDesc('performed_on')->orderByDesc('id')->first();
            if ($last && $last->result === 'fail') {
                return __('Failed its last calibration');
            }
            $plan = FacMaintenancePlan::query()->where(['asset_id' => $asset->id, 'kind' => 'calibration', 'active' => true])->orderBy('next_due_on')->first();
            if ($plan && $plan->next_due_on->lt(today())) {
                return __('Calibration is overdue');
            }
        }

        return null;
    }

    public function findByToken(string $token, int $organizationId): ?FacAsset
    {
        return preg_match('/^[a-f0-9]{32}$/', $token) ? FacAsset::query()->where(['qr_token' => $token, 'organization_id' => $organizationId])->first() : null;
    }

    /** The label's QR code: an address that needs a staff sign-in; the token carries no patient data. */
    public function qrSvg(FacAsset $asset, int $size = 160): string
    {
        return (string) QrCode::format('svg')->size($size)->margin(1)->generate(url('/admin/facility/scan/'.$asset->qr_token));
    }

    private function clip(mixed $v, int $n): ?string
    {
        $v = trim(strip_tags((string) $v));

        return $v === '' ? null : Str::limit($v, $n, '');
    }
}
