<?php

namespace App\Services\Ops;

use App\Models\Ipd\IpdAdmission;
use App\Models\Nursing\NursingMedicationAdministration;
use App\Models\Organization;
use App\Services\Ux\PatientBannerData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Read-only list of current inpatients with allergies and upcoming medicines (guideline 21.6). Generated on a schedule and stored as a
 * self-contained HTML file, so it can be opened and printed when the application itself is down.
 */
class DowntimeCensus
{
    public function __construct(private readonly PatientBannerData $banner) {}

    /** @return array{path: string, patients: int}|null null when nobody is admitted */
    public function generate(Organization $organization): ?array
    {
        $admissions = IpdAdmission::query()->where('organization_id', $organization->id)->where('status', 'admitted')
            ->with(['patient', 'currentAllocation.bed.room.ward', 'attendingProvider'])->get();

        if ($admissions->isEmpty()) {
            return null;
        }

        $rows = $admissions->map(function (IpdAdmission $admission) {
            $patient = $admission->patient;
            $banner = $this->banner->for($patient);
            $bed = $admission->currentAllocation?->bed;

            return [
                'ward' => $bed?->room?->ward?->name ?? '—', 'bed' => $bed?->bed_code ?? '—', 'name' => $banner['name'], 'mrn' => $banner['number'],
                'age_sex' => trim(($banner['age'] ?? '').' '.($banner['sex'] ?? '')), 'admission_number' => $admission->admission_number,
                'doctor' => $admission->attendingProvider?->name, 'allergies' => array_column($banner['allergies'], 'substance'),
                'alerts' => array_column($banner['alerts'], 'title'), 'medicines' => $this->medicines($admission),
            ];
        })->sortBy(['ward', 'bed'])->values()->all();

        $html = view('admin.downtime.census', [
            'organization' => $organization, 'rows' => $rows, 'generatedAt' => now()->timezone(config('documents.timezone')),
            'training' => app(TrainingMode::class)->active($organization),
        ])->render();

        $disk = config('ops.backup.disk');
        $path = sprintf('downtime/%d/census-%s.html', $organization->id, now()->format('Ymd-Hi'));
        Storage::disk($disk)->put($path, $html);
        DB::table('downtime_censuses')->insert(['organization_id' => $organization->id, 'disk' => $disk, 'path' => $path, 'patient_count' => count($rows), 'generated_at' => now()]);
        $this->prune($organization);

        return ['path' => $path, 'patients' => count($rows)];
    }

    /** Medicines scheduled from 12 hours ago to 36 hours ahead, one line each with dose and route. @return list<string> */
    private function medicines(IpdAdmission $admission): array
    {
        return NursingMedicationAdministration::query()->where('admission_id', $admission->id)->with('medication')
            ->whereBetween('scheduled_at', [now()->subHours(12), now()->addHours(36)])
            ->whereNotIn('status', [NursingMedicationAdministration::STATUS_CANCELLED])
            ->orderBy('scheduled_at')->get()
            ->map(fn ($m) => trim(($m->medication?->name ?? '?').' '.$m->dose.' '.$m->dose_unit.' '.$m->route).($m->is_prn ? ' (PRN)' : ''))
            ->unique()->values()->all();
    }

    private function prune(Organization $organization): void
    {
        DB::table('downtime_censuses')->where('organization_id', $organization->id)->where('generated_at', '<', now()->subDays((int) config('ops.downtime.keep_days')))
            ->get()->each(function ($row) {
                Storage::disk($row->disk)->delete($row->path);
                DB::table('downtime_censuses')->where('id', $row->id)->delete();
            });
    }
}
