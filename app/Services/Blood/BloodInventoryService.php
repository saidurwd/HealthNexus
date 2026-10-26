<?php

namespace App\Services\Blood;

use App\Models\Blood\BloodStorageLocation;
use App\Models\Blood\BloodTemperatureLog;
use App\Models\Blood\BloodUnit;
use App\Models\User;
use App\Services\Clinical\Alerts\AlertRequest;
use App\Services\Clinical\Alerts\AlertService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Where each bag is, how cold it is, which are close to expiry, and which to use first (FEFO). Blood is not stock: none of this touches the Stock Ledger. */
class BloodInventoryService
{
    public function __construct(
        private readonly BloodPolicy $policy,
        private readonly BloodUnitService $units,
        private readonly BloodTestingService $testing,
        private readonly AlertService $alerts,
    ) {}

    /**
     * Records a fridge / freezer reading. A reading outside the location's range is an excursion: it raises a critical alert and flags every
     * unit stored there (blocked, with the reason) until the blood-bank medical officer decides what to do with them.
     */
    public function recordTemperature(BloodStorageLocation $location, float $celsius, ?User $by = null, string $source = 'manual'): BloodTemperatureLog
    {
        return DB::transaction(function () use ($location, $celsius, $by, $source) {
            $excursion = $celsius < (float) $location->min_temp_c || $celsius > (float) $location->max_temp_c;
            $log = BloodTemperatureLog::create([
                'organization_id' => $location->organization_id, 'location_id' => $location->id, 'celsius' => $celsius, 'source' => $source,
                'is_excursion' => $excursion, 'recorded_at' => now(), 'recorded_by' => $by?->id,
            ]);

            if ($excursion) {
                $affected = BloodUnit::query()->where('location_id', $location->id)->whereIn('status', [BloodUnit::AVAILABLE, BloodUnit::RESERVED, BloodUnit::QUARANTINED])->get();
                foreach ($affected as $unit) {
                    $this->units->move($unit, BloodUnit::BLOCKED, $by, "Temperature {$celsius} °C outside {$location->min_temp_c}–{$location->max_temp_c} °C", $log, ['blocked_reason' => 'temperature_excursion']);
                }
                $this->alerts->raise(new AlertRequest(
                    organizationId: $location->organization_id, category: 'blood_temperature_excursion', severity: 'critical', sourceModule: 'bloodbank', branchId: $location->branch_id,
                    message: "{$location->name}: {$celsius} °C is outside {$location->min_temp_c}–{$location->max_temp_c} °C. {$affected->count()} unit(s) blocked.",
                    sourceType: BloodStorageLocation::class, sourceId: $location->id, recipientUserIds: $this->testing->roleUsers($location->organization_id),
                    dedupKey: "blood-temp:{$location->id}:".now()->format('YmdH'), raisedBy: $by?->id,
                ));
            }

            return $log;
        });
    }

    /** Marks every unit past its expiry as expired and frees its reservation. @return int units expired */
    public function expireDue(?int $organizationId = null): int
    {
        $count = 0;
        BloodUnit::query()->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))
            ->whereIn('status', [BloodUnit::QUARANTINED, BloodUnit::AVAILABLE, BloodUnit::RESERVED, BloodUnit::BLOCKED])->where('expires_at', '<=', now())->orderBy('id')
            ->each(function (BloodUnit $unit) use (&$count) {
                DB::table('blood_reservations')->where('unit_id', $unit->id)->where('status', 'active')->update(['status' => 'expired', 'released_at' => now(), 'updated_at' => now()]);
                $this->units->move($unit, BloodUnit::EXPIRED, null, 'Passed its expiry date');
                $count++;
            });

        return $count;
    }

    /** Units that expire within the warning window; one alert per organization per day. @return int units listed */
    public function warnNearExpiry(int $organizationId): int
    {
        $soon = BloodUnit::query()->where('organization_id', $organizationId)->where('status', BloodUnit::AVAILABLE)
            ->whereBetween('expires_at', [now(), now()->addDays((int) $this->policy->get('expiry_alert_days'))])->get();
        if ($soon->isNotEmpty()) {
            $this->alerts->raise(new AlertRequest(
                organizationId: $organizationId, category: 'blood_expiry_soon', severity: 'warning', sourceModule: 'bloodbank',
                message: $soon->count().' blood unit(s) expire within '.$this->policy->get('expiry_alert_days').' days: '.$soon->take(5)->pluck('unit_number')->implode(', ').'.',
                recipientUserIds: $this->testing->roleUsers($organizationId), dedupKey: 'blood-expiry:'.now()->format('Ymd'),
            ));
        }

        return $soon->count();
    }

    /** @return array<string, int> component => available units below its minimum level, with the minimum */
    public function lowStock(int $organizationId): array
    {
        $available = BloodUnit::query()->where('organization_id', $organizationId)->where('status', BloodUnit::AVAILABLE)->where('expires_at', '>', now())
            ->selectRaw('component, count(*) as total')->groupBy('component')->pluck('total', 'component');
        $low = [];
        foreach ($this->policy->get('low_stock') as $component => $minimum) {
            if (($available[$component] ?? 0) < $minimum) {
                $low[$component] = (int) ($available[$component] ?? 0);
            }
        }

        return $low;
    }

    /** First-expiring-first-out: usable units of a component, soonest expiry first. @return Collection<int, BloodUnit> */
    public function fefo(int $organizationId, string $component, ?string $abo = null, ?string $rh = null): Collection
    {
        return BloodUnit::query()->where('organization_id', $organizationId)->where('component', $component)->where('status', BloodUnit::AVAILABLE)->where('expires_at', '>', now())
            ->when($abo, fn ($q) => $q->where('abo', $abo))->when($rh, fn ($q) => $q->where('rh', $rh))->orderBy('expires_at')->orderBy('id')->get();
    }

    /** @return array<string, array<string, int>> "A positive" => component => available units */
    public function summary(int $organizationId): array
    {
        $rows = BloodUnit::query()->where('organization_id', $organizationId)->where('status', BloodUnit::AVAILABLE)->where('expires_at', '>', now())
            ->selectRaw('abo, rh, component, count(*) as total')->groupBy('abo', 'rh', 'component')->get();
        $out = [];
        foreach ($rows as $row) {
            $out[trim("{$row->abo} {$row->rh}")][$row->component] = (int) $row->total;
        }
        ksort($out);

        return $out;
    }
}
