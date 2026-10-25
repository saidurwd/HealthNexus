<?php

namespace Tests\Feature\Ot;

use App\Models\Ot\OtSurgery;
use App\Services\Ot\OtReceptionService;
use App\Services\Ot\OtSchedulingService;
use App\Services\Ot\OtSurgeryService;
use Illuminate\Validation\ValidationException;

class NegativeOtTest extends OtTestCase
{
    public function test_cannot_create_surgery_without_a_patient(): void
    {
        $encounter = \App\Models\Encounter::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        app(OtSurgeryService::class)->create([
            'organization_id' => $this->organization->id, 'branch_id' => $this->branch->id,
            'patient_id' => null, 'encounter_id' => $encounter->id,
        ], $this->user);
    }

    public function test_inactive_theatre_cannot_be_scheduled(): void
    {
        $surgery = $this->makeDaySurgery();
        $theatre = $this->makeTheatre();
        $theatre->update(['is_active' => false]);

        $this->expectException(ValidationException::class);

        app(OtSchedulingService::class)->schedule($surgery, [
            'theatre_id' => $theatre->id, 'scheduled_start_at' => now(), 'scheduled_end_at' => now()->addHours(2),
        ], $this->user);
    }

    public function test_blocked_theatre_cannot_be_scheduled(): void
    {
        $surgery = $this->makeDaySurgery();
        $theatre = $this->makeTheatre();
        $theatre->update(['status' => 'blocked']);

        $this->expectException(ValidationException::class);

        app(OtSchedulingService::class)->schedule($surgery, [
            'theatre_id' => $theatre->id, 'scheduled_start_at' => now(), 'scheduled_end_at' => now()->addHours(2),
        ], $this->user);
    }

    public function test_cancelled_surgery_cannot_be_started(): void
    {
        $surgery = $this->makeDaySurgery();
        $theatre = $this->makeTheatre();
        app(OtSchedulingService::class)->schedule($surgery, [
            'theatre_id' => $theatre->id, 'scheduled_start_at' => now(), 'scheduled_end_at' => now()->addHours(2),
        ], $this->user);
        app(OtSchedulingService::class)->cancel($surgery->fresh(), 'patient refused', $this->user);

        $this->expectException(ValidationException::class);
        app(OtReceptionService::class)->receive($surgery->fresh(), $this->user);
    }

    public function test_completed_surgery_cannot_be_silently_reopened(): void
    {
        $surgery = $this->progressToCompleted($this->makeDaySurgery());

        $this->assertSame(OtSurgery::STATUS_PROCEDURE_COMPLETED, $surgery->status);

        $this->expectException(ValidationException::class);
        app(OtReceptionService::class)->start($surgery->fresh(), $this->user);
    }

    public function test_surgeon_double_booking_in_overlapping_window_is_rejected(): void
    {
        $surgeon = $this->makeSurgeon();
        $theatreA = $this->makeTheatre();
        $theatreB = $this->makeTheatre();
        $surgeryA = $this->makeDaySurgery();
        $surgeryB = $this->makeDaySurgery();

        $start = now()->addDay()->setTime(9, 0);
        $end = now()->addDay()->setTime(11, 0);

        app(OtSchedulingService::class)->schedule($surgeryA, [
            'theatre_id' => $theatreA->id, 'scheduled_start_at' => $start, 'scheduled_end_at' => $end, 'primary_surgeon_id' => $surgeon->id,
        ], $this->user);

        $this->expectException(ValidationException::class);

        app(OtSchedulingService::class)->schedule($surgeryB, [
            'theatre_id' => $theatreB->id,
            'scheduled_start_at' => $start->copy()->addMinutes(30),
            'scheduled_end_at' => $end->copy()->addMinutes(30),
            'primary_surgeon_id' => $surgeon->id,
        ], $this->user);
    }
}
