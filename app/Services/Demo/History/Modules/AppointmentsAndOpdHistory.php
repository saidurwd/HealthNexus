<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Appointment;
use App\Models\Laboratory\LabTest;
use App\Models\Patient;
use App\Models\Pharmacy\PharmacyMedication;
use App\Models\Radiology\RadiologyProcedure;
use App\Models\User;
use App\Services\Appointments\AppointmentBookingService;
use App\Services\Appointments\AppointmentLifecycleService;
use App\Services\Clinical\EncounterClinicalService;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use App\Services\EncounterLifecycleService;
use App\Services\EncounterService;
use Carbon\CarbonImmutable;

/**
 * Appointments, queue tokens and the OPD consultation, every working day: booked, confirmed, checked in, seen (vitals, diagnosis,
 * prescription, lab and imaging orders) and completed — with some cancellations and no-shows. The prescriptions and orders flow on to
 * Pharmacy, Laboratory, Radiology and Billing through the real listeners, which the later history classes then progress.
 */
class AppointmentsAndOpdHistory implements DemoHistoryModule
{
    private const COMPLAINTS = ['Fever for 3 days', 'Cough and cold', 'Abdominal pain', 'Headache', 'Chest discomfort', 'Back pain', 'Diabetes follow-up', 'High blood pressure review', 'Skin rash', 'Sore throat', 'Joint pain', 'Breathlessness'];

    private const DIAGNOSES = [['J06.9', 'Acute upper respiratory infection'], ['A09', 'Gastroenteritis'], ['I10', 'Essential hypertension'], ['E11.9', 'Type 2 diabetes mellitus'], ['R51', 'Headache'], ['M54.5', 'Low back pain'], ['K29.7', 'Gastritis'], ['L30.9', 'Dermatitis'], ['J45.9', 'Asthma'], ['B34.9', 'Viral infection']];

    public function __construct(
        private readonly AppointmentBookingService $booking,
        private readonly AppointmentLifecycleService $lifecycle,
        private readonly EncounterService $encounters,
        private readonly EncounterLifecycleService $encounterLifecycle,
        private readonly EncounterClinicalService $clinical,
    ) {}

    public function name(): string
    {
        return 'Appointments and OPD';
    }

    public function module(): string
    {
        return 'opd';
    }

    public function run(HistoryContext $context): string
    {
        $patients = $context->patients();
        $doctors = $context->staff('doctor');
        $labTests = LabTest::query()->forTenant($context->organization->id)->where('is_active', true)->get();
        $procedures = RadiologyProcedure::query()->forTenant($context->organization->id)->where('is_active', true)->get();
        $medicines = PharmacyMedication::query()->where('organization_id', $context->organization->id)->where('is_active', true)->get();

        $encounters = [];
        $counts = ['appointments' => 0, 'cancelled' => 0, 'no_show' => 0, 'prescriptions' => 0, 'lab_orders' => 0, 'imaging_orders' => 0];

        foreach ($context->days() as $day) {
            if ($day->isFriday()) {
                continue;
            }
            $visits = max(1, $context->visitsPerDay + mt_rand(-2, 2));
            $slot = [];

            for ($i = 0; $i < $visits; $i++) {
                $doctor = $context->pick($doctors);
                $minute = $slot[$doctor->id] = ($slot[$doctor->id] ?? -15) + 15;
                $time = $day->setTime(9, 0)->addMinutes($minute);
                $patient = $context->pick($patients);

                $appointment = $context->at($time->subDay()->setTime(16, 0), fn () => $this->book($context, $patient, $doctor, $time));
                $counts['appointments']++;

                if ($context->chance(8)) {
                    $context->at($time->subHours(3), fn () => $this->lifecycle->cancel($appointment, 'Patient asked to cancel', $context->actor));
                    $counts['cancelled']++;

                    continue;
                }
                if ($context->chance(5)) {
                    $context->at($time->addHour(), fn () => $this->lifecycle->markNoShow($appointment, 'Did not arrive', $context->actor));
                    $counts['no_show']++;

                    continue;
                }

                $context->at($time, function () use ($context, $appointment, $doctor, $patient, $labTests, $procedures, $medicines, &$counts, &$encounters, $time) {
                    $this->lifecycle->confirm($appointment, $context->actor);
                    $this->lifecycle->checkIn($appointment->refresh(), $context->staff('receptionist')->first());
                    $this->lifecycle->startConsultation($appointment->refresh(), $doctor);
                    $encounter = $this->encounters->findOrCreateFromAppointment($appointment->refresh(), $doctor);
                    $this->encounters->updateEncounter($encounter, ['provider_id' => $doctor->id, 'chief_complaint_summary' => $context->pick(self::COMPLAINTS)]);
                    $encounter = $encounter->refresh();

                    $this->clinical->addVital($encounter, $this->vitals(), $context->staff('staff_nurse')->first());
                    [$code, $description] = $context->pick(self::DIAGNOSES);
                    $this->clinical->addDiagnosis($encounter, ['code' => $code, 'coding_system' => 'ICD-10', 'description' => $description, 'is_primary' => true, 'doctor_id' => $doctor->id], $doctor);

                    if ($medicines->isNotEmpty() && $context->chance(80)) {
                        $items = collect(range(1, mt_rand(1, 3)))->map(fn () => [
                            'medicine_name' => $context->pick($medicines)->name, 'frequency' => $context->pick(['1+0+1', '1+1+1', '0+0+1', 'SOS']), 'duration' => $context->pick(['3 days', '5 days', '7 days', '14 days']), 'quantity' => mt_rand(6, 30),
                        ])->unique('medicine_name')->values()->all();
                        $prescription = $this->clinical->createPrescription($encounter, ['doctor_id' => $doctor->id, 'items' => $items, 'advice' => 'Drink plenty of fluids and rest.'], $doctor);
                        $this->clinical->issuePrescription($encounter, $prescription, $doctor);
                        $counts['prescriptions']++;
                    }
                    if ($labTests->isNotEmpty() && $context->chance(35)) {
                        $tests = $labTests->random(min($labTests->count(), mt_rand(1, 3)));
                        $this->clinical->createOrder($encounter, ['order_type' => 'laboratory', 'priority' => $context->chance(10) ? 'urgent' : 'routine', 'provider_id' => $doctor->id,
                            'items' => $tests->map(fn ($t) => ['item_name' => $t->name, 'item_code' => $t->code, 'quantity' => 1, 'status' => 'requested'])->all()], $doctor);
                        $counts['lab_orders']++;
                    }
                    if ($procedures->isNotEmpty() && $context->chance(12)) {
                        $procedure = $context->pick($procedures);
                        $this->clinical->createOrder($encounter, ['order_type' => 'radiology', 'priority' => 'routine', 'provider_id' => $doctor->id,
                            'items' => [['item_name' => $procedure->name, 'item_code' => $procedure->code, 'quantity' => 1, 'status' => 'requested']]], $doctor);
                        $counts['imaging_orders']++;
                    }

                    $context->at($time->addMinutes(12), function () use ($appointment, $encounter, $doctor) {
                        $this->encounterLifecycle->complete($encounter, $doctor);
                        $this->lifecycle->complete($appointment->refresh(), $doctor);
                    });

                    $encounters[] = ['id' => $encounter->id, 'patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'at' => $time->toIso8601String()];
                });
            }
        }

        $context->share('encounters', $encounters);

        return sprintf('%d appointments (%d cancelled, %d no-show), %d consultations, %d prescriptions, %d lab orders, %d imaging orders',
            $counts['appointments'], $counts['cancelled'], $counts['no_show'], count($encounters), $counts['prescriptions'], $counts['lab_orders'], $counts['imaging_orders']);
    }

    private function book(HistoryContext $context, Patient $patient, User $doctor, CarbonImmutable $time): Appointment
    {
        return $this->booking->book([
            'organization_id' => $context->organization->id, 'branch_id' => $context->branch->id, 'patient_id' => $patient->id, 'doctor_id' => $doctor->id,
            'appointment_date' => $time->toDateString(), 'appointment_time' => $time->format('H:i'), 'type' => 'scheduled', 'source' => $context->pick(['phone', 'walkin', 'online']),
            'reason' => $context->pick(self::COMPLAINTS),
        ], $context->actor);
    }

    /** @return array<string, float|int> */
    private function vitals(): array
    {
        return [
            'temperature' => round(36.2 + mt_rand(0, 30) / 10, 1), 'systolic' => mt_rand(100, 160), 'diastolic' => mt_rand(60, 100), 'pulse_rate' => mt_rand(60, 105),
            'respiratory_rate' => mt_rand(12, 22), 'oxygen_saturation' => mt_rand(93, 100), 'height' => mt_rand(150, 183), 'weight' => mt_rand(45, 95),
        ];
    }
}
