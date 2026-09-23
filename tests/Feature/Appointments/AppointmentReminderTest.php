<?php

namespace Tests\Feature\Appointments;

use App\Events\Appointments\AppointmentCreated;
use App\Jobs\Appointments\SendAppointmentReminderJob;
use App\Models\Appointment;
use App\Models\AppointmentReminder;
use App\Models\AppointmentReminderRule;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Patient;
use App\Models\User;
use App\Services\Appointments\AppointmentReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AppointmentReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduling_reminders_creates_one_row_per_matching_rule(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $patient = Patient::factory()->create(['company_id' => $company->id]);
        $doctor = User::factory()->create();

        AppointmentReminderRule::create([
            'company_id' => $company->id, 'channel' => 'email', 'offset_minutes' => 24 * 60, 'is_active' => true,
        ]);
        AppointmentReminderRule::create([
            'company_id' => $company->id, 'channel' => 'sms', 'offset_minutes' => 120, 'is_active' => true,
        ]);
        // Inactive rule must be skipped.
        AppointmentReminderRule::create([
            'company_id' => $company->id, 'channel' => 'push', 'offset_minutes' => 30, 'is_active' => false,
        ]);

        $appointment = Appointment::create([
            'company_id' => $company->id, 'branch_id' => $branch->id, 'patient_id' => $patient->id, 'doctor_id' => $doctor->id,
            'appointment_no' => 'APT-REM-1', 'appointment_date' => now()->addDays(3)->toDateString(), 'appointment_time' => '10:00',
            'type' => 'scheduled', 'source' => 'online',
        ]);

        $created = app(AppointmentReminderService::class)->scheduleRemindersFor($appointment);

        $this->assertCount(2, $created);
        $this->assertSame(2, AppointmentReminder::where('appointment_id', $appointment->id)->count());
        $this->assertDatabaseHas('appointment_reminders', ['appointment_id' => $appointment->id, 'channel' => 'email', 'status' => 'pending']);
        $this->assertDatabaseHas('appointment_reminders', ['appointment_id' => $appointment->id, 'channel' => 'sms', 'status' => 'pending']);
        $this->assertDatabaseMissing('appointment_reminders', ['appointment_id' => $appointment->id, 'channel' => 'push']);
    }

    public function test_appointment_created_event_triggers_reminder_scheduling(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $patient = Patient::factory()->create(['company_id' => $company->id]);
        $doctor = User::factory()->create();

        AppointmentReminderRule::create([
            'company_id' => $company->id, 'channel' => 'email', 'offset_minutes' => 60, 'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'company_id' => $company->id, 'branch_id' => $branch->id, 'patient_id' => $patient->id, 'doctor_id' => $doctor->id,
            'appointment_no' => 'APT-REM-2', 'appointment_date' => now()->addDays(1)->toDateString(), 'appointment_time' => '09:00',
            'type' => 'scheduled', 'source' => 'online',
        ]);

        AppointmentCreated::dispatch($appointment);

        $this->assertDatabaseHas('appointment_reminders', ['appointment_id' => $appointment->id, 'channel' => 'email']);
    }

    public function test_cancelling_an_appointment_cancels_its_pending_reminders(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $patient = Patient::factory()->create(['company_id' => $company->id]);
        $doctor = User::factory()->create();

        $appointment = Appointment::create([
            'company_id' => $company->id, 'branch_id' => $branch->id, 'patient_id' => $patient->id, 'doctor_id' => $doctor->id,
            'appointment_no' => 'APT-REM-3', 'appointment_date' => now()->addDays(1)->toDateString(), 'appointment_time' => '09:00',
            'type' => 'scheduled', 'source' => 'online', 'status' => 'scheduled',
        ]);

        $reminder = AppointmentReminder::create([
            'company_id' => $company->id, 'appointment_id' => $appointment->id, 'channel' => 'email',
            'scheduled_for' => now()->addHours(2), 'status' => 'pending',
        ]);

        app(\App\Services\Appointments\AppointmentLifecycleService::class)->cancel($appointment, 'Patient request');

        $this->assertSame('cancelled', $reminder->fresh()->status);
    }

    public function test_due_reminders_command_dispatches_a_job_per_due_reminder(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $patient = Patient::factory()->create(['company_id' => $company->id]);
        $doctor = User::factory()->create();

        $appointment = Appointment::create([
            'company_id' => $company->id, 'branch_id' => $branch->id, 'patient_id' => $patient->id, 'doctor_id' => $doctor->id,
            'appointment_no' => 'APT-REM-4', 'appointment_date' => now()->toDateString(), 'appointment_time' => now()->addHour()->format('H:i'),
            'type' => 'scheduled', 'source' => 'online',
        ]);

        AppointmentReminder::create([
            'company_id' => $company->id, 'appointment_id' => $appointment->id, 'channel' => 'email',
            'scheduled_for' => now()->subMinute(), 'status' => 'pending',
        ]);
        AppointmentReminder::create([
            'company_id' => $company->id, 'appointment_id' => $appointment->id, 'channel' => 'sms',
            'scheduled_for' => now()->addDay(), 'status' => 'pending',
        ]);

        $this->artisan('appointments:send-due-reminders')->assertExitCode(0);

        Queue::assertPushed(SendAppointmentReminderJob::class, 1);
    }

    public function test_send_reminder_job_marks_sent_and_emails_the_patient(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $patient = Patient::factory()->create(['company_id' => $company->id, 'email' => 'patient@example.com']);
        $doctor = User::factory()->create();

        $appointment = Appointment::create([
            'company_id' => $company->id, 'branch_id' => $branch->id, 'patient_id' => $patient->id, 'doctor_id' => $doctor->id,
            'appointment_no' => 'APT-REM-5', 'appointment_date' => now()->addDay()->toDateString(), 'appointment_time' => '09:00',
            'type' => 'scheduled', 'source' => 'online',
        ]);

        $reminder = AppointmentReminder::create([
            'company_id' => $company->id, 'appointment_id' => $appointment->id, 'channel' => 'email',
            'scheduled_for' => now()->subMinute(), 'status' => 'pending',
        ]);

        (new SendAppointmentReminderJob($reminder))->handle();

        $this->assertSame('sent', $reminder->fresh()->status);
        $this->assertNotNull($reminder->fresh()->sent_at);
        \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\SystemNotification::class);
    }
}
