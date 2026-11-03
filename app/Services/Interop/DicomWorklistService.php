<?php

namespace App\Services\Interop;

use App\Models\Integration\DicomWorklistEntry;
use App\Models\Integration\IntegrationPartner;
use App\Models\Patient;
use App\Models\Radiology\RadiologyExamination;
use App\Models\Radiology\RadiologyModality;
use App\Models\Radiology\RadiologyOrder;
use App\Models\Radiology\RadiologyOrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * DICOM Modality Worklist (S09): the list a scanner asks for to learn which patient is next, built from radiology orders so the technologist never types a name into the machine. The accession
 * number is the key that ties the scanner's images back to the order. Entries follow the exam: scheduled → in progress → completed, or cancelled.
 */
class DicomWorklistService
{
    public function __construct(private readonly InteropPolicy $policy) {}

    /** Makes entries for exams that have none and brings existing entries in line with their exam. @return array{created:int,updated:int} */
    public function sync(int $organizationId): array
    {
        $created = $updated = 0;
        RadiologyExamination::query()->where('organization_id', $organizationId)->whereIn('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])
            ->where(fn ($q) => $q->whereIn('status', ['scheduled', 'in_progress'])->orWhere('updated_at', '>=', now()->subDays(2)))->orderBy('id')->each(function (RadiologyExamination $exam) use (&$created, &$updated) {
                $entry = DicomWorklistEntry::query()->where('examination_id', $exam->id)->first();
                $status = match ($exam->status) {
                    'scheduled' => 'scheduled', 'in_progress' => 'in_progress', 'completed' => 'completed', default => 'cancelled'
                };
                if ($entry === null) {
                    if ($status !== 'scheduled') {
                        return;
                    }
                    $this->make($exam);
                    $created++;
                } elseif ($entry->status !== $status && ! ($entry->status === 'in_progress' && $status === 'scheduled')) {
                    $entry->update(['status' => $status]);
                    $updated++;
                }
            });

        return ['created' => $created, 'updated' => $updated];
    }

    public function make(RadiologyExamination $exam): DicomWorklistEntry
    {
        $item = RadiologyOrderItem::query()->findOrFail($exam->order_item_id);
        $order = RadiologyOrder::query()->findOrFail($item->radiology_order_id);
        $patient = Patient::query()->findOrFail($exam->patient_id);
        $modality = $exam->modality_id ? RadiologyModality::query()->find($exam->modality_id) : null;

        return DicomWorklistEntry::create([
            'organization_id' => $exam->organization_id, 'branch_id' => $exam->branch_id, 'examination_id' => $exam->id, 'patient_id' => $patient->id, 'accession_number' => (string) $order->accession_number,
            'patient_name' => $this->personName($patient), 'patient_mrn' => $patient->enterprise_patient_no, 'birth_date' => $patient->date_of_birth?->toDateString(),
            'sex' => match (strtoupper((string) $patient->sex)) {
                'M' => 'M', 'F' => 'F', default => 'O'
            },
            'modality' => $this->modalityCode($modality?->modality_type), 'procedure_description' => mb_substr((string) $item->requested_procedure_name, 0, 200), 'requested_procedure_id' => 'RP'.$item->id,
            'station_ae_title' => strtoupper(mb_substr((string) ($modality?->ae_title ?: $this->policy->get('dicom.default_station_ae', 'HNWORKLIST')), 0, 16)), 'scheduled_at' => $exam->scheduled_at,
            'referring_physician' => $order->ordered_by ? $this->personNameOfUser((int) $order->ordered_by) : null, 'status' => 'scheduled',
        ]);
    }

    /** DICOM person name: FAMILY^GIVEN^MIDDLE (no accents stripped; the standard allows UTF-8 with the right character set). */
    public function personName(Patient $p): string
    {
        return implode('^', [$this->clean($p->last_name), $this->clean($p->first_name), $this->clean($p->middle_name)]);
    }

    private function personNameOfUser(int $userId): string
    {
        $name = trim((string) User::query()->whereKey($userId)->value('name'));
        $pos = strrpos($name, ' ');

        return $pos === false ? $this->clean($name) : $this->clean(substr($name, $pos + 1)).'^'.$this->clean(substr($name, 0, $pos));
    }

    private function clean(?string $v): string
    {
        return str_replace(['^', '\\', '='], ' ', trim((string) $v));
    }

    private function modalityCode(?string $type): string
    {
        $t = strtoupper(trim((string) $type));
        $map = ['XRAY' => 'CR', 'X-RAY' => 'CR', 'XR' => 'CR', 'RADIOGRAPHY' => 'CR', 'CT' => 'CT', 'MRI' => 'MR', 'MR' => 'MR', 'ULTRASOUND' => 'US', 'US' => 'US', 'MAMMOGRAPHY' => 'MG', 'MG' => 'MG', 'FLUOROSCOPY' => 'RF', 'DEXA' => 'BMD', 'NUCLEAR' => 'NM', 'PET' => 'PT'];

        return $map[$t] ?? (preg_match('/^[A-Z]{2,3}$/', $t) ? $t : 'OT');
    }

    /**
     * @param  array{modality?:string,ae?:string,date?:string,accession?:string,patient_id?:string,status?:string}  $f
     * @return Builder<DicomWorklistEntry>
     */
    public function query(int $organizationId, array $f): Builder
    {
        return DicomWorklistEntry::query()->where('organization_id', $organizationId)
            ->when($f['modality'] ?? null, fn ($q, $v) => $q->where('modality', strtoupper($v)))
            ->when($f['ae'] ?? null, fn ($q, $v) => $q->where('station_ae_title', strtoupper($v)))
            ->when($f['date'] ?? null, fn ($q, $v) => $q->whereDate('scheduled_at', $v))
            ->when($f['accession'] ?? null, fn ($q, $v) => $q->where('accession_number', $v))
            ->when($f['patient_id'] ?? null, fn ($q, $v) => $q->where('patient_mrn', $v))
            ->where('status', $f['status'] ?? 'scheduled')
            ->where(fn ($q) => $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now()->addDays($this->policy->int('dicom.worklist_days', 3))))
            ->orderBy('scheduled_at')->orderBy('id');
    }

    /** What the scanner reports back about the step (MPPS in miniature). */
    public function reportStatus(int $organizationId, string $accession, string $status, ?IntegrationPartner $partner = null): DicomWorklistEntry
    {
        if (! in_array($status, ['in_progress', 'completed', 'cancelled'], true)) {
            throw new InteropException('status must be in_progress, completed or cancelled', 422, 'invalid');
        }
        $entry = DicomWorklistEntry::query()->where(['organization_id' => $organizationId, 'accession_number' => $accession])->first();
        if ($entry === null) {
            throw new InteropException('no worklist entry has that accession number', 404, 'not-found');
        }
        if (in_array($entry->status, ['completed', 'cancelled'], true) && $entry->status !== $status) {
            throw new InteropException("the entry is already {$entry->status}", 409, 'conflict');
        }
        $entry->update(['status' => $status]);

        return $entry;
    }
}
