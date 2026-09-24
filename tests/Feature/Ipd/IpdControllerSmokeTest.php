<?php

namespace Tests\Feature\Ipd;

use App\Models\Encounter;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdBedMovement;
use App\Models\Patient;
use App\Services\Ipd\IpdBedAllocationService;
use App\Services\Ipd\IpdDischargeService;
use App\Services\Ipd\IpdLeaveService;
use App\Services\Ipd\IpdTransferService;

/**
 * Exercises every GET view end-to-end against real seeded rows — the same technique that caught
 * real bugs in Laboratory's, Radiology's, and Pharmacy's controller smoke tests.
 */
class IpdControllerSmokeTest extends IpdTestCase
{
    private IpdAdmission $admission;

    private IpdBedMovement $transfer;

    private \App\Models\Ipd\IpdDischargeRequest $dischargeRequest;

    private \App\Models\Ipd\IpdPatientLeave $leave;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $bed = $this->makeBed();
        $destinationBed = $this->makeBed();
        $thirdBed = $this->makeBed();

        $this->patient = Patient::factory()->create(['company_id' => $this->company->id]);
        $encounter = Encounter::factory()->create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'patient_id' => $this->patient->id,
        ]);
        $this->admission = IpdAdmission::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'admission_number' => 'ADM-TEST-'.uniqid(), 'patient_id' => $this->patient->id,
            'encounter_id' => $encounter->id, 'admitted_at' => now(), 'status' => 'admitted',
        ]);
        app(IpdBedAllocationService::class)->allocate($this->admission, $bed, $this->user);
        $this->admission = $this->admission->fresh();

        $this->transfer = app(IpdTransferService::class)->request($this->admission, $destinationBed, $this->user);

        // Discharge needs its own admission — a discharge cannot be planned while a transfer is
        // still pending on the same admission (correctly rejected by IpdAdmissionLifecycleService).
        $dischargePatient = Patient::factory()->create(['company_id' => $this->company->id]);
        $dischargeEncounter = Encounter::factory()->create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'patient_id' => $dischargePatient->id,
        ]);
        $dischargeAdmission = IpdAdmission::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'admission_number' => 'ADM-TEST-'.uniqid(), 'patient_id' => $dischargePatient->id,
            'encounter_id' => $dischargeEncounter->id, 'admitted_at' => now(), 'status' => 'admitted',
        ]);
        $dischargeBed = $this->makeBed();
        app(IpdBedAllocationService::class)->allocate($dischargeAdmission, $dischargeBed, $this->user);
        $this->dischargeRequest = app(IpdDischargeService::class)->request($dischargeAdmission->fresh(), ['discharge_type' => 'routine'], $this->user);

        // Leave needs its own admitted patient too — use a fresh admission on the third bed.
        $leavePatient = Patient::factory()->create(['company_id' => $this->company->id]);
        $leaveEncounter = Encounter::factory()->create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'patient_id' => $leavePatient->id,
        ]);
        $leaveAdmission = IpdAdmission::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'admission_number' => 'ADM-TEST-'.uniqid(), 'patient_id' => $leavePatient->id,
            'encounter_id' => $leaveEncounter->id, 'admitted_at' => now(), 'status' => 'admitted',
        ]);
        app(IpdBedAllocationService::class)->allocate($leaveAdmission, $thirdBed, $this->user);
        $this->leave = app(IpdLeaveService::class)->request($leaveAdmission->fresh(), [
            'leave_type' => 'temporary_pass', 'expected_return_at' => now()->addHours(4),
        ], $this->user);
    }

    public function test_dashboard_loads(): void
    {
        $this->get('/admin/ipd')->assertOk();
    }

    public function test_ward_room_bed_pages_load(): void
    {
        $this->get('/admin/ipd/wards')->assertOk();
        $this->get('/admin/ipd/wards/create')->assertOk();
        $this->get('/admin/ipd/rooms')->assertOk();
        $this->get('/admin/ipd/rooms/create')->assertOk();
        $this->get('/admin/ipd/beds')->assertOk();
        $this->get('/admin/ipd/beds/create')->assertOk();
    }

    public function test_bed_board_and_availability_pages_load(): void
    {
        $this->get('/admin/ipd/bed-board')->assertOk();
        $this->get('/admin/ipd/bed-availability')->assertOk();
    }

    public function test_admission_request_pages_load(): void
    {
        $this->get('/admin/ipd/admission-requests')->assertOk();
        $this->get('/admin/ipd/admission-requests/create')->assertOk();
    }

    public function test_admission_pages_load(): void
    {
        $this->get('/admin/ipd/admissions')->assertOk();
        $this->get('/admin/ipd/admissions/'.$this->admission->id)->assertOk();
        $this->get('/admin/ipd/admissions/create')->assertOk();
    }

    public function test_transfer_pages_load(): void
    {
        $this->get('/admin/ipd/transfers')->assertOk();
        $this->get('/admin/ipd/transfers/'.$this->transfer->id)->assertOk();
        $this->get('/admin/ipd/admissions/'.$this->admission->id.'/transfer')->assertOk();
    }

    public function test_discharge_pages_load(): void
    {
        $this->get('/admin/ipd/discharge')->assertOk();
        $this->get('/admin/ipd/discharge/'.$this->dischargeRequest->id)->assertOk();
    }

    public function test_leave_pages_load(): void
    {
        $this->get('/admin/ipd/leave')->assertOk();
        $this->get('/admin/ipd/leave/'.$this->leave->id)->assertOk();
    }

    public function test_report_pages_load(): void
    {
        $this->get('/admin/ipd/reports/occupancy')->assertOk();
        $this->get('/admin/ipd/reports/admissions')->assertOk();
        $this->get('/admin/ipd/reports/discharges')->assertOk();
        $this->get('/admin/ipd/reports/transfers')->assertOk();
    }

    public function test_settings_page_loads(): void
    {
        $this->get('/admin/ipd/settings')->assertOk();
    }

    public function test_patient_history_page_loads(): void
    {
        $this->get('/admin/patients/'.$this->patient->id.'/ipd-history')->assertOk();
    }
}
