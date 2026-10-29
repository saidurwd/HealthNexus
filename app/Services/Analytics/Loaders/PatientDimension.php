<?php

namespace App\Services\Analytics\Loaders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Patients as the warehouse sees them: a pseudonym, an age band, sex, district and status — nothing that names or reaches a person. Slowly changing (type 2): when one of those changes
 * the old row is closed and a new one opens, so a fact keeps the description that was true when it happened.
 */
class PatientDimension extends Loader
{
    public function key(): string
    {
        return 'dim_patient';
    }

    public function label(): string
    {
        return 'Patients (pseudonymised)';
    }

    public function kind(): string
    {
        return 'dimension';
    }

    protected function target(): string
    {
        return 'dim_patient';
    }

    protected function uniqueBy(): array
    {
        return ['patient_key'];
    }

    protected function source(): Builder
    {
        return DB::table('patients')->select('patients.id', 'patients.organization_id', 'patients.date_of_birth', 'patients.estimated_age', 'patients.estimated_age_unit', 'patients.sex', 'patients.city', 'patients.state', 'patients.status', 'patients.deceased_at', 'patients.updated_at');
    }

    protected function watermarkColumn(): string
    {
        return 'patients.updated_at';
    }

    protected function idColumn(): string
    {
        return 'patients.id';
    }

    public static function ageBand(?int $years): string
    {
        return match (true) {
            $years === null => 'unknown', $years < 1 => '<1', $years <= 4 => '1-4', $years <= 14 => '5-14', $years <= 24 => '15-24', $years <= 44 => '25-44', $years <= 64 => '45-64', default => '65+',
        };
    }

    private function years(object $r): ?int
    {
        if ($r->date_of_birth) {
            return (int) CarbonImmutable::parse($r->date_of_birth)->diffInYears(now());
        }
        if ($r->estimated_age !== null) {
            return $r->estimated_age_unit === 'months' ? intdiv((int) $r->estimated_age, 12) : (int) $r->estimated_age;
        }

        return null;
    }

    /** This loader writes versions itself (insert / close), so it overrides the generic upsert. */
    public function load(?string $watermark): array
    {
        $read = $written = 0;
        $max = $watermark;
        $from = $watermark ? CarbonImmutable::parse($watermark)->subHours((int) config('analytics.load.overlap_hours', 2))->toDateTimeString() : null;
        $today = now()->setTimezone((string) config('analytics.timezone', 'Asia/Dhaka'))->toDateString();
        $this->source()->when($from, fn ($q) => $q->where('patients.updated_at', '>', $from))->chunkById($this->chunk(), function (Collection $rows) use (&$read, &$written, &$max, $today) {
            $pseudo = $rows->mapWithKeys(fn ($r) => [$r->id => $this->warehouse->pseudonym($r->id, $r->organization_id)]);
            $current = $this->warehouse->table('dim_patient')->whereIn('pseudonym', $pseudo->values()->all())->where('is_current', true)->get()->keyBy('pseudonym');
            $new = [];
            foreach ($rows as $r) {
                $attrs = ['age_band' => self::ageBand($this->years($r)), 'sex' => $r->sex ? (string) $r->sex : 'unknown', 'district' => $r->city ?: ($r->state ?: null), 'patient_status' => $r->status, 'deceased' => $r->deceased_at !== null];
                $hash = md5(json_encode($attrs));
                $row = $current->get($pseudo[$r->id]);
                if ($row && $row->attr_hash === $hash) {
                    continue;
                }
                if ($row) {
                    $this->warehouse->table('dim_patient')->where('patient_key', $row->patient_key)->update(['is_current' => false, 'valid_to' => $today]);
                }
                $new[] = ['pseudonym' => $pseudo[$r->id], 'organization_id' => $r->organization_id] + $attrs + ['valid_from' => $row ? $today : '1900-01-01', 'valid_to' => null, 'is_current' => true, 'attr_hash' => $hash];
            }
            foreach (array_chunk($new, 100) as $part) {
                $this->warehouse->table('dim_patient')->insert($part);
            }
            $written += count($new);
            $read += $rows->count();
            $m = $rows->max('updated_at');
            $max = $m !== null && ($max === null || strcmp((string) $m, (string) $max) > 0) ? (string) $m : $max;
        }, 'patients.id', 'id');

        return ['read' => $read, 'written' => $written, 'watermark' => $max];
    }
}
