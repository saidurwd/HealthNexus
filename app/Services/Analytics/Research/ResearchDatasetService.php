<?php

namespace App\Services\Analytics\Research;

use App\Models\Analytics\AnalyticsDataset;
use App\Models\User;
use App\Services\Analytics\Warehouse;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Datasets for research, made by a de-identification pipeline: the patient becomes a reference made fresh for each dataset (so two datasets cannot be joined), dates are reduced to the month or
 * year, only listed columns can be taken, and any group of people smaller than k is left out. The key that could tie a reference back to a person is the application key and the hospital's own
 * records; it is not in the warehouse and not in the file. A second person approves each dataset before it is made.
 */
class ResearchDatasetService
{
    /** @var array<string, array{table:string,label:string,columns:array<string,string>,quasi:list<string>}> */
    public const SOURCES = [
        'encounters' => ['table' => 'fact_encounters', 'label' => 'Visits', 'columns' => ['month' => 'month', 'visit_type' => 'f.encounter_type', 'status' => 'f.status', 'priority' => 'f.priority', 'source' => 'f.source', 'duration_minutes' => 'f.duration_minutes', 'age_band' => 'p.age_band', 'sex' => 'p.sex', 'district' => 'p.district'],
            'quasi' => ['month', 'visit_type', 'age_band', 'sex', 'district']],
        'admissions' => ['table' => 'fact_admissions', 'label' => 'Admissions', 'columns' => ['month' => 'month', 'status' => 'f.status', 'length_of_stay_days' => 'f.los_days', 'discharged' => 'f.is_discharged', 'age_band' => 'p.age_band', 'sex' => 'p.sex', 'district' => 'p.district'],
            'quasi' => ['month', 'age_band', 'sex', 'district']],
        'lab' => ['table' => 'fact_lab_orders', 'label' => 'Laboratory orders', 'columns' => ['month' => 'month', 'priority' => 'f.priority', 'tests' => 'f.items', 'turn_around_minutes' => 'f.tat_report_min', 'critical_results' => 'f.critical_results', 'abnormal_results' => 'f.abnormal_results', 'age_band' => 'p.age_band', 'sex' => 'p.sex'],
            'quasi' => ['month', 'priority', 'age_band', 'sex']],
    ];

    public function __construct(private readonly Warehouse $warehouse, private readonly AuditLogger $audit) {}

    /** @param array{source:string,columns:list<string>,from:string,to:string} $definition */
    public function request(int $organizationId, User $by, string $name, string $purpose, array $definition): AnalyticsDataset
    {
        $src = self::SOURCES[$definition['source']] ?? null;
        if ($src === null) {
            throw ValidationException::withMessages(['source' => 'Choose what the dataset is about.']);
        }
        $cols = array_values(array_unique($definition['columns']));
        if ($cols === [] || array_diff($cols, array_keys($src['columns'])) !== []) {
            throw ValidationException::withMessages(['columns' => 'Choose columns from the list; others cannot be taken.']);
        }
        if (trim($name) === '' || mb_strlen(trim($purpose)) < 20) {
            throw ValidationException::withMessages(['purpose' => 'Say what the data is for (at least a sentence); the approver reads it.']);
        }
        $from = CarbonImmutable::parse($definition['from'])->toDateString();
        $to = CarbonImmutable::parse($definition['to'])->toDateString();
        if ($from > $to) {
            throw ValidationException::withMessages(['from' => 'The period ends before it starts.']);
        }

        return AnalyticsDataset::create(['organization_id' => $organizationId, 'name' => mb_substr(trim($name), 0, 150), 'purpose' => mb_substr(trim($purpose), 0, 400), 'definition' => ['source' => $definition['source'], 'columns' => $cols, 'from' => $from, 'to' => $to], 'status' => 'requested', 'requested_by' => $by->id]);
    }

    public function decide(AnalyticsDataset $dataset, bool $approve, User $by, ?string $note = null): AnalyticsDataset
    {
        if ($dataset->status !== 'requested') {
            throw ValidationException::withMessages(['status' => 'This request was already decided.']);
        }
        if ($dataset->requested_by === $by->id) {
            throw ValidationException::withMessages(['approver' => 'A dataset must be approved by someone other than the person who asked for it.']);
        }
        $dataset->update(['status' => $approve ? 'approved' : 'rejected', 'decided_by' => $by->id, 'decided_at' => now(), 'decision_note' => $note ? mb_substr($note, 0, 300) : null]);
        $this->audit->log('ANALYTICS_DATASET_'.($approve ? 'APPROVED' : 'REJECTED'), AnalyticsDataset::class, $dataset->id, null, ['name' => $dataset->name, 'by' => $by->id]);

        return $dataset->refresh();
    }

    /** Make the file. Rows in a group smaller than k (by the columns that could single a person out) are left out and counted. */
    public function generate(AnalyticsDataset $dataset, User $by): AnalyticsDataset
    {
        if ($dataset->status !== 'approved') {
            throw ValidationException::withMessages(['status' => 'Only an approved dataset can be made.']);
        }
        $def = $dataset->definition;
        $src = self::SOURCES[$def['source']];
        $dateCol = $def['source'] === 'encounters' || $def['source'] === 'admissions' || $def['source'] === 'lab' ? 'f.date_key' : 'f.date_key';
        $query = $this->warehouse->table($src['table'].' as f')->leftJoin('dim_patient as p', 'p.patient_key', '=', 'f.patient_key')->where('f.organization_id', $dataset->organization_id)
            ->whereBetween($dateCol, [(int) str_replace('-', '', $def['from']), (int) str_replace('-', '', $def['to'])])->limit((int) config('analytics.research.max_rows', 100000));
        $select = ['f.date_key as _date', 'p.pseudonym as _pseudo'];
        foreach ($def['columns'] as $c) {
            if ($src['columns'][$c] !== 'month') {
                $select[] = $src['columns'][$c].' as '.$c;
            }
        }
        $rows = $query->get($select);
        $salt = hash_hmac('sha256', 'dataset:'.$dataset->public_id, (string) config('app.key'));
        $k = max(2, (int) config('analytics.research.k_anonymity', 5));
        $quasi = array_values(array_intersect($src['quasi'], $def['columns']));
        $byYear = config('analytics.research.dates', 'month') === 'year';
        $prepared = [];
        foreach ($rows as $r) {
            $row = [];
            foreach ($def['columns'] as $c) {
                $row[$c] = $src['columns'][$c] === 'month' ? ($byYear ? substr((string) $r->_date, 0, 4) : substr((string) $r->_date, 0, 4).'-'.substr((string) $r->_date, 4, 2)) : ($r->{$c} ?? null);
            }
            $prepared[] = ['patient_ref' => $r->_pseudo ? substr(hash_hmac('sha256', $r->_pseudo, $salt), 0, 16) : null] + $row;
        }
        $groups = [];
        foreach ($prepared as $i => $row) {
            $groups[implode('|', array_map(fn ($q) => (string) ($row[$q] ?? ''), $quasi))][] = $i;
        }
        $kept = [];
        $suppressed = 0;
        foreach ($groups as $idx) {
            if (count($idx) < $k) {
                $suppressed += count($idx);

                continue;
            }
            foreach ($idx as $i) {
                $kept[] = $prepared[$i];
            }
        }
        $header = array_merge(['patient_ref'], $def['columns']);
        $out = fopen('php://temp', 'r+');
        fputcsv($out, $header);
        foreach ($kept as $row) {
            fputcsv($out, array_map(fn ($h) => $row[$h] ?? '', $header));
        }
        rewind($out);
        $csv = (string) stream_get_contents($out);
        $path = 'analytics/datasets/'.$dataset->public_id.'.csv';
        Storage::disk('local')->put($path, $csv);
        $dataset->update(['status' => 'generated', 'generated_at' => now(), 'row_count' => count($kept), 'suppressed_rows' => $suppressed, 'file' => $path, 'checksum' => hash('sha256', $csv)]);
        $this->audit->log('ANALYTICS_DATASET_GENERATED', AnalyticsDataset::class, $dataset->id, null, ['rows' => count($kept), 'suppressed' => $suppressed, 'k' => $k, 'by' => $by->id]);

        return $dataset->refresh();
    }

    public function contents(AnalyticsDataset $dataset, User $by): string
    {
        if ($dataset->status !== 'generated' || ! $dataset->file) {
            throw ValidationException::withMessages(['status' => 'The dataset has not been made yet.']);
        }
        $this->audit->log('ANALYTICS_DATASET_DOWNLOADED', AnalyticsDataset::class, $dataset->id, null, ['by' => $by->id]);

        return (string) Storage::disk('local')->get($dataset->file);
    }
}
