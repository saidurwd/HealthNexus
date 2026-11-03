<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Geo\GeoDistrict;
use App\Models\Patient;
use App\Models\Watch\SvCondition;
use App\Models\Watch\SvSignal;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use App\Services\Watch\SurveillanceService;
use App\Services\Watch\WatchInstaller;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Disease watch, the last eight weeks of the history. The starting list of conditions is installed. Dengue is steady and then climbs for three weeks, mostly from one district; cholera shows up as a small cluster in the last week; typhoid
 * and others stay under their limits. The scan runs at the end of each of the last five weeks, so signals appear as they would have. The officer then takes them up: the oldest are confirmed and reported to the civil surgeon's office, one is
 * dismissed, one is being looked into, and the newest are waiting. Nothing here draws random numbers.
 */
class WatchHistory implements DemoHistoryModule
{
    /** Distinct patients with dengue in each of the last eight weeks, oldest first. */
    private const DENGUE = [1, 2, 1, 2, 2, 4, 7, 10];

    private const TYPHOID = [2, 3, 2, 3, 2, 3, 2, 3];

    public function __construct(private readonly WatchInstaller $installer, private readonly SurveillanceService $surveillance) {}

    public function name(): string
    {
        return 'Disease watch';
    }

    public function module(): string
    {
        return 'watch';
    }

    public function run(HistoryContext $context): string
    {
        $org = $context->organization->id;
        $officer = $context->staff('surveillance_officer')->first();
        $context->at($context->from->setTime(8, 0), fn () => $this->installer->install($org));
        $doctor = $context->staff('doctor')->first();
        $patients = Patient::query()->where('organization_id', $org)->whereNull('deceased_at')->orderBy('id')->get()->values();
        if ($patients->count() < 12) {
            return 'too few patients for a surveillance story';
        }
        $homes = $this->homes();
        $to = $context->to;
        $given = 0;
        $cursor = 0;
        $write = function (string $code, int $weeksAgo, int $n, ?int $district = null) use (&$cursor, &$given, $patients, $doctor, $to, $org, $context) {
            for ($i = 0; $i < $n; $i++) {
                $p = $patients[$cursor++ % $patients->count()];
                $at = $to->subWeeks($weeksAgo)->subDays($i % 5)->setTime(10 + $i % 6, 15);
                DB::table('diagnoses')->insert(['organization_id' => $org, 'branch_id' => $context->branch->id, 'patient_id' => $p->id, 'doctor_id' => $doctor->id, 'code_type' => 'ICD-10', 'coding_system' => 'ICD-10', 'code' => $code, 'description' => 'Notifiable disease (demo)',
                    'status' => 'confirmed', 'diagnosis_type' => 'primary', 'is_primary' => true, 'recorded_by' => $doctor->id, 'recorded_at' => $at, 'created_at' => $at, 'updated_at' => $at]);
                if ($district && $i % 3 !== 2) {
                    $this->liveIn($p, $district, $at);
                }
                $given++;
            }
        };

        foreach (self::DENGUE as $k => $n) {
            $weeksAgo = 7 - $k;
            $write($k % 2 ? 'A91' : 'A90', $weeksAgo, $n, $weeksAgo <= 2 ? $homes['dhaka'] : null);
        }
        foreach (self::TYPHOID as $k => $n) {
            $write('A01.0', 7 - $k, $n);
        }
        $write('A00.9', 0, 2, $homes['gazipur']);
        $write('A00.1', 1, 1);

        $raised = 0;
        foreach ([4, 3, 2, 1, 0] as $weeksAgo) {
            $on = $to->subWeeks($weeksAgo)->setTime(18, 0);
            $context->at($on, function () use ($org, $on, &$raised) {
                $raised += $this->surveillance->scan($org, $on)['raised'];
            });
        }

        foreach (SvSignal::query()->where('organization_id', $org)->orderBy('week_start')->orderBy('id')->get()->values() as $i => $s) {
            try {
                $at = $s->raised_at->addHours(5);
                match ($i % 5) {
                    0 => $context->at($at, function () use ($s, $officer, $at, $context) {
                        $this->surveillance->decide($s->refresh(), true, 'Cases are rising and come from the same area; confirmed on review of the records.', $officer);
                        $context->at($at->addHours(2), fn () => $this->surveillance->report($s->refresh(), 'Civil Surgeon office', 'CS/'.$at->format('Y').'/'.(100 + $s->id), $officer));
                    }),
                    1 => $context->at($at, fn () => $this->surveillance->decide($s->refresh(), false, 'A batch of older results entered together; no real rise.', $officer)),
                    2 => $context->at($at, fn () => $this->surveillance->investigate($s->refresh(), $officer)),
                    default => null,
                };
            } catch (Throwable) {
                // a signal left as it is
            }
        }

        $n = SvCondition::query()->where('organization_id', $org)->count();

        return sprintf('%d conditions watched, %d diagnoses written, %d signals raised (%d confirmed and reported)', $n, $given, SvSignal::query()->where('organization_id', $org)->count(), SvSignal::query()->where('organization_id', $org)->whereNotNull('reported_at')->count());
    }

    /** @return array{dhaka:int,gazipur:int} */
    private function homes(): array
    {
        return ['dhaka' => (int) GeoDistrict::query()->where('name_en', 'Dhaka')->value('id'), 'gazipur' => (int) GeoDistrict::query()->where('name_en', 'Gazipur')->value('id')];
    }

    private function liveIn(Patient $p, int $districtId, $at): void
    {
        if ($districtId === 0) {
            return;
        }
        DB::table('patient_addresses')->where(['patient_id' => $p->id, 'address_type' => 'permanent'])->whereNull('deleted_at')->update(['district_id' => $districtId, 'division_id' => GeoDistrict::query()->whereKey($districtId)->value('division_id'), 'updated_at' => $at]);
        if (! DB::table('patient_addresses')->where(['patient_id' => $p->id, 'address_type' => 'permanent'])->exists()) {
            DB::table('patient_addresses')->insert(['organization_id' => $p->organization_id, 'patient_id' => $p->id, 'address_type' => 'permanent', 'district_id' => $districtId, 'division_id' => GeoDistrict::query()->whereKey($districtId)->value('division_id'), 'is_primary' => true, 'created_at' => $at, 'updated_at' => $at]);
        }
    }
}
