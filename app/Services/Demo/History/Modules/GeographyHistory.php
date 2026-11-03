<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Geo\GeoDistrict;
use App\Models\Geo\GeoUpazila;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use App\Services\Geo\GeoInstaller;
use App\Services\Geo\GeoService;
use App\Services\Patients\PatientService;
use Illuminate\Support\Collection;

/**
 * The country's places and where the patients live. The geography is installed (the same file a real hospital uses), the hospital lists the unions of one upazila it serves most, and every patient gets a permanent address
 * (and most a present one) with the division, district and upazila chosen: mostly the home district and its neighbours, the rest scattered across the country, as a referral hospital's patients are.
 */
class GeographyHistory implements DemoHistoryModule
{
    /** Home district first, then the neighbours, with the share of patients each has (the rest are spread over the whole country). */
    private const CATCHMENT = ['Dhaka' => 45, 'Gazipur' => 12, 'Narayanganj' => 10, 'Manikganj' => 6, 'Narsingdi' => 5];

    private const SAVAR_UNIONS = ['Aminbazar', 'Ashulia', 'Bhakurta', 'Birulia', 'Dhamsona', 'Pathalia', 'Shimulia', 'Tetuljhora'];

    public function __construct(private readonly GeoInstaller $installer, private readonly GeoService $geo, private readonly PatientService $patients) {}

    public function name(): string
    {
        return 'Geography and patient addresses';
    }

    public function module(): string
    {
        return 'patient';
    }

    public function run(HistoryContext $context): string
    {
        $installed = $this->installer->install();
        $savar = GeoUpazila::query()->where('name_en', 'Savar')->whereHas('district', fn ($d) => $d->where('name_en', 'Dhaka'))->first();
        foreach ($savar ? self::SAVAR_UNIONS : [] as $union) {
            $this->geo->addUnion($savar->id, $union);
        }
        $all = GeoDistrict::query()->get();
        $near = $all->whereIn('name_en', array_keys(self::CATCHMENT))->keyBy('name_en');
        $added = 0;

        foreach ($context->patients() as $patient) {
            if ($patient->addresses()->where('address_type', 'permanent')->exists()) {
                continue;
            }
            $permanent = $this->district($near, $all);
            $context->at($patient->created_at ?? $context->from, function () use ($patient, $permanent, $context, $savar, &$added) {
                $this->patients->addAddress($patient, $this->address('permanent', $permanent, $savar, $context) + ['is_primary' => true]);
                $added++;
                if ($context->chance(70)) {
                    $present = $context->chance(60) ? $permanent : $this->district(collect(), GeoDistrict::query()->whereIn('name_en', ['Dhaka', 'Gazipur', 'Narayanganj'])->get());
                    $this->patients->addAddress($patient, $this->address('present', $present, $savar, $context) + ['is_primary' => true]);
                    $added++;
                }
            });
        }

        return sprintf('%d divisions, %d districts and %d upazilas installed; %d unions listed for Savar; %d patient addresses with a district chosen', $installed['divisions'], $installed['districts'], $installed['upazilas'], $savar ? count(self::SAVAR_UNIONS) : 0, $added);
    }

    /** @param  Collection<string, GeoDistrict>  $near  @param  \Illuminate\Support\Collection<int, GeoDistrict>  $all */
    private function district($near, $all): GeoDistrict
    {
        $roll = mt_rand(1, 100);
        $edge = 0;
        foreach (self::CATCHMENT as $name => $share) {
            $edge += $share;
            if ($roll <= $edge && isset($near[$name])) {
                return $near[$name];
            }
        }

        return $all->random();
    }

    /** @return array<string,mixed> */
    private function address(string $type, GeoDistrict $district, ?GeoUpazila $savar, HistoryContext $context): array
    {
        $upazila = $district->upazilas()->inRandomOrder()->first();
        $union = $upazila && $savar && $upazila->id === $savar->id ? $upazila->unions()->inRandomOrder()->first() : null;

        return ['address_type' => $type, 'division_id' => $district->division_id, 'district_id' => $district->id, 'upazila_id' => $upazila?->id, 'union_id' => $union?->id, 'village_area' => $context->pick(['Uttar Para', 'Dakshin Para', 'Bazar Road', 'College Road', 'Station Road', 'Purba Para']), 'line1' => null];
    }
}
