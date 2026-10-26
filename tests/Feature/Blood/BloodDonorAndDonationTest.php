<?php

namespace Tests\Feature\Blood;

use App\Models\Blood\BloodDonor;
use App\Models\Blood\BloodUnit;
use App\Services\Blood\BloodDonationService;
use App\Services\Blood\BloodDonorService;
use App\Services\Blood\BloodProcessingService;
use App\Services\Blood\BloodTestingService;
use Illuminate\Validation\ValidationException;

class BloodDonorAndDonationTest extends BloodTestCase
{
    public function test_a_donor_is_a_person_in_the_register_with_a_numbered_identity_and_cannot_be_registered_twice(): void
    {
        $donor = $this->donor();
        $this->assertStringStartsWith('DNR-', $donor->donor_number);
        $this->assertNotNull($donor->consent_at);

        $this->expectException(ValidationException::class);
        app(BloodDonorService::class)->register($this->user, $this->branch, ['patient_id' => $donor->patient_id]);
    }

    public function test_screening_needs_consent(): void
    {
        $donor = app(BloodDonorService::class)->register($this->user, $this->branch, ['first_name' => 'A', 'last_name' => 'B', 'sex' => 'M', 'date_of_birth' => now()->subYears(30)->toDateString()]);
        $this->expectException(ValidationException::class);
        $this->goodScreening($donor);
    }

    public function test_a_failed_rule_defers_the_donor_and_a_deferred_donor_cannot_donate(): void
    {
        $donor = $this->donor();
        $screening = $this->goodScreening($donor, ['hemoglobin' => 10.0]);

        $this->assertSame('deferred', $screening->outcome);
        $this->assertContains('hemoglobin', $screening->failed_rules);
        $this->assertSame(BloodDonor::DEFERRED, $donor->refresh()->status);
        $this->assertTrue($donor->isDeferred());

        $this->expectException(ValidationException::class);
        app(BloodDonationService::class)->start($donor, $screening, $this->branch, $this->user);
    }

    public function test_the_eligibility_rules_are_editable_policy_not_code(): void
    {
        app(\App\Services\SettingsService::class)->set('blood.eligibility', ['min_age' => 18, 'max_age' => 60, 'min_weight_kg' => 50, 'min_hb' => ['M' => 9.0, 'F' => 9.0], 'min_interval_days' => ['M' => 90, 'F' => 120],
            'pulse' => [50, 100], 'systolic' => [100, 180], 'diastolic' => [60, 100], 'max_temp_c' => 37.5], 'blood');
        $this->assertSame('eligible', $this->goodScreening($this->donor(), ['hemoglobin' => 10.0])->outcome);
    }

    public function test_a_recent_donation_defers_until_the_interval_has_passed(): void
    {
        $donor = $this->donor();
        app(BloodDonationService::class)->complete(app(BloodDonationService::class)->start($donor, $this->goodScreening($donor), $this->branch, $this->user), 450, $this->user);

        $second = $this->goodScreening($donor->refresh());
        $this->assertSame('deferred', $second->outcome);
        $this->assertContains('interval', $second->failed_rules);
        $this->assertTrue($second->deferred_until->gte(today()->addDays(89)));
    }

    public function test_a_screening_cannot_be_reused_and_one_donation_runs_at_a_time(): void
    {
        $donor = $this->donor();
        $screening = $this->goodScreening($donor);
        $service = app(BloodDonationService::class);
        $donation = $service->start($donor, $screening, $this->branch, $this->user);

        try {
            $service->start($donor, $screening, $this->branch, $this->user);
            $this->fail('second donation accepted');
        } catch (ValidationException) {
        }
        $service->complete($donation, 450, $this->user);
        $this->expectException(ValidationException::class);
        $service->start($donor, $screening, $this->branch, $this->user);
    }

    public function test_collection_makes_a_numbered_quarantined_whole_blood_unit_and_orders_the_lab_tests(): void
    {
        $donor = $this->donor();
        $donation = app(BloodDonationService::class)->complete(app(BloodDonationService::class)->start($donor, $this->goodScreening($donor), $this->branch, $this->user), 450, $this->user);

        $this->assertStringStartsWith('DON-', $donation->donation_number);
        $unit = BloodUnit::where('donation_id', $donation->id)->firstOrFail();
        $this->assertSame(BloodUnit::QUARANTINED, $unit->status);
        $this->assertSame($donation->donation_number.'-00', $unit->unit_number);
        $this->assertSame(35, (int) round($unit->collected_at->diffInDays($unit->expires_at, true)));
        $this->assertNotNull($donation->observation_until);

        // the tests are a Laboratory order, not a second result store
        $this->assertDatabaseHas('clinical_orders', ['id' => $donation->clinical_order_id, 'order_type' => 'laboratory']);
        $this->assertSame(7, \App\Models\Laboratory\LabOrder::where('clinical_order_id', $donation->clinical_order_id)->firstOrFail()->items()->count());
        $this->assertSame(7, $donation->tests()->count());
    }

    public function test_adverse_event_is_recorded_and_unknown_types_are_refused(): void
    {
        $donor = $this->donor();
        $donation = app(BloodDonationService::class)->start($donor, $this->goodScreening($donor), $this->branch, $this->user);
        $event = app(BloodDonationService::class)->recordAdverseEvent($donation, $this->user, 'vasovagal', 'mild', 'Felt faint', 'Lay down, fluids');
        $this->assertSame('vasovagal', $event->event_type);

        $this->expectException(ValidationException::class);
        app(BloodDonationService::class)->recordAdverseEvent($donation, $this->user, 'made_up', 'mild');
    }

    public function test_components_are_made_from_whole_blood_once_and_stay_quarantined_until_tests_release_them(): void
    {
        $donor = $this->donor();
        $donation = app(BloodDonationService::class)->complete(app(BloodDonationService::class)->start($donor, $this->goodScreening($donor), $this->branch, $this->user), 450, $this->user);
        $whole = BloodUnit::where('donation_id', $donation->id)->firstOrFail();
        $made = app(BloodProcessingService::class)->separate($whole, $this->user);

        $this->assertCount(3, $made);
        $this->assertEqualsCanonicalizing(['prbc', 'ffp', 'platelets'], collect($made)->pluck('component')->all());
        $this->assertTrue(collect($made)->every(fn ($u) => $u->status === BloodUnit::QUARANTINED));
        $this->assertSame(5, (int) round($made[2]->collected_at->diffInDays($made[2]->expires_at, true)), 'platelets last 5 days');
        $this->assertSame(BloodUnit::PROCESSED, $whole->refresh()->status);

        // nothing can leave quarantine before every test has passed
        try {
            app(BloodTestingService::class)->release($donation, $this->user);
            $this->fail('released untested units');
        } catch (ValidationException) {
        }
        $this->assertSame(3, BloodUnit::where('donation_id', $donation->id)->where('status', BloodUnit::QUARANTINED)->count());

        try {
            app(BloodProcessingService::class)->separate($whole->refresh(), $this->user);
            $this->fail('separated twice');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
    }
}
