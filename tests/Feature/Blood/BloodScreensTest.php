<?php

namespace Tests\Feature\Blood;

use App\Contracts\Integration\BloodRequestGateway;
use App\Models\Blood\BloodDonation;
use App\Models\Blood\BloodDonor;
use App\Models\Blood\BloodDonorScreening;
use App\Models\Blood\BloodIncident;
use App\Models\Blood\BloodRequest;
use App\Models\Blood\BloodStorageLocation;
use App\Models\Blood\BloodUnit;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use App\Services\Blood\BloodIssueService;
use App\Services\Blood\BloodTransfusionService;
use App\Services\Demo\DemoAccess;
use App\Services\ModuleManager;
use Database\Seeders\PermissionSeeder;
use Spatie\Permission\PermissionRegistrar;

class BloodScreensTest extends BloodTestCase
{
    public function test_every_blood_bank_screen_opens_for_the_super_user(): void
    {
        $donor = $this->donor();
        $unit = $this->unit('prbc', 'A', 'positive');
        $request = $this->readyRequest('A', 'positive');
        $this->reserve($request, $unit);
        $issue = app(BloodIssueService::class)->issue($request, $unit->refresh(), $this->user);
        $patient = Patient::find($issue->patient_id);
        $transfusion = app(BloodTransfusionService::class)->start($issue, $this->staff(), $this->staff(), $patient->public_id, $unit->unit_number);
        $donation = BloodDonation::first();
        $incident = BloodIncident::create(['organization_id' => $this->organization->id, 'incident_type' => 'near_miss', 'description' => 'x', 'occurred_at' => now()]);

        foreach ([
            '', '/donors', '/donors/create', "/donors/{$donor->public_id}", "/donations/{$donation->public_id}", '/inventory', '/inventory/storage', "/units/{$unit->public_id}",
            '/requests', '/requests/create', "/requests/{$request->public_id}", '/transfusions', "/transfusions/{$transfusion->public_id}", '/incidents', '/lookback', "/lookback?q={$donor->donor_number}", "/lookback?q={$unit->unit_number}", '/reports', '/reports?days=90',
        ] as $path) {
            $this->get('/admin/blood-bank'.$path)->assertOk();
        }
        $this->assertNotNull($incident);
    }

    public function test_the_forms_work_end_to_end_on_screen(): void
    {
        $base = '/admin/blood-bank';
        $this->post("$base/donors", ['first_name' => 'Nadia', 'last_name' => 'Akter', 'sex' => 'F', 'date_of_birth' => now()->subYears(28)->toDateString(), 'consent' => 1])->assertRedirect();
        $donor = BloodDonor::firstOrFail();
        $this->post("$base/donors/{$donor->public_id}/screening", ['weight_kg' => 60, 'hemoglobin' => 13.5, 'systolic' => 118, 'diastolic' => 76, 'pulse' => 70, 'temperature_c' => 36.7])->assertRedirect()->assertSessionHas('success');
        $screening = BloodDonorScreening::firstOrFail();
        $this->post("$base/donors/{$donor->public_id}/donations", ['screening' => $screening->public_id])->assertRedirect();
        $donation = BloodDonation::firstOrFail();
        $this->post("$base/donations/{$donation->public_id}/complete", ['volume_ml' => 450])->assertRedirect()->assertSessionHas('success');
        $this->post("$base/donations/{$donation->public_id}/separate")->assertRedirect()->assertSessionHas('success');
        $this->post("$base/donations/{$donation->public_id}/observation")->assertRedirect();
        $this->passTests($donation->refresh(), 'O', 'negative');
        $this->post("$base/donations/{$donation->public_id}/release")->assertRedirect()->assertSessionHas('success');
        $this->assertSame(3, BloodUnit::where('status', 'available')->count());

        $fridge = BloodStorageLocation::where('kind', 'fridge')->firstOrFail();
        $this->post("$base/inventory/storage/{$fridge->public_id}/temperature", ['celsius' => 12])->assertRedirect()->assertSessionHas('warning');
        $this->assertSame(1, BloodUnit::where('status', 'blocked')->count());

        $patient = $this->patient();
        $this->post("$base/requests", ['patient' => $patient->enterprise_patient_no, 'component' => 'prbc', 'units_requested' => 1, 'priority' => 'urgent'])->assertRedirect();
        $request = BloodRequest::firstOrFail();
        $this->post("$base/requests/{$request->public_id}/sample", ['patient_scan' => $patient->public_id, 'abo' => 'A', 'rh' => 'positive', 'collected_at' => now()->subHour()->format('Y-m-d\TH:i'), 'labelled' => 1])->assertRedirect()->assertSessionHas('success');
        $this->assertSame('requested', $request->refresh()->status);
    }

    public function test_the_gateway_creates_a_real_request_and_answers_not_enabled_when_the_module_is_off(): void
    {
        $patient = $this->patient();
        $gateway = app(BloodRequestGateway::class);
        $result = $gateway->requestUnits(['patient_id' => $patient->id, 'user_id' => $this->user->id, 'component' => 'prbc', 'units' => 2, 'priority' => 'urgent', 'source_module' => 'icu']);

        $this->assertTrue($result->handled);
        $this->assertDatabaseHas('blood_requests', ['patient_id' => $patient->id, 'source_module' => 'icu', 'units_requested' => 2]);

        $bad = $gateway->requestUnits(['patient_id' => $patient->id, 'user_id' => $this->user->id, 'component' => 'prbc', 'units' => 99]);
        $this->assertFalse($bad->handled);

        app(ModuleManager::class)->set('bloodbank', false, $this->organization->id);
        $off = $gateway->requestUnits(['patient_id' => $patient->id, 'user_id' => $this->user->id, 'component' => 'prbc', 'units' => 1]);
        $this->assertFalse($off->handled);
        $this->assertStringContainsString('not enabled', $off->reason);
        $this->get('/admin/blood-bank')->assertNotFound();
    }

    public function test_the_emergency_gateway_releases_the_pack(): void
    {
        $this->releasedDonation('O', 'negative');
        $result = app(BloodRequestGateway::class)->releaseEmergencyUnits(['patient_id' => $this->patient()->id, 'user_id' => $this->user->id, 'authorised_by' => 'Dr. Karim', 'reason' => 'Trauma, massive bleeding', 'source_module' => 'ed']);

        $this->assertTrue($result->handled);
        $this->assertSame(2, $result->data['issued'], 'one O-negative red cell and one platelet bag; no AB plasma');
    }

    private function routes(): array
    {
        $donor = $this->donor();
        $unit = $this->unit('prbc', 'A', 'positive');
        $request = $this->readyRequest('A', 'positive');
        $this->reserve($request, $unit);
        $issue = app(BloodIssueService::class)->issue($request, $unit->refresh(), $this->user);
        $transfusion = app(BloodTransfusionService::class)->start($issue, $this->staff(), $this->staff(), Patient::find($issue->patient_id)->public_id, $unit->unit_number);
        $donation = BloodDonation::first();
        $location = BloodStorageLocation::first();
        $incident = BloodIncident::create(['organization_id' => $this->organization->id, 'incident_type' => 'near_miss', 'description' => 'x', 'occurred_at' => now()]);
        $b = '/admin/blood-bank';

        return [
            ['GET', "$b/donors/{$donor->public_id}"], ['POST', "$b/donors/{$donor->public_id}/screening"], ['POST', "$b/donors/{$donor->public_id}/donations"], ['GET', "$b/donations/{$donation->public_id}"],
            ['POST', "$b/donations/{$donation->public_id}/complete"], ['POST', "$b/donations/{$donation->public_id}/separate"], ['POST', "$b/donations/{$donation->public_id}/release"], ['POST', "$b/donations/{$donation->public_id}/sync-tests"],
            ['GET', "$b/units/{$unit->public_id}"], ['POST', "$b/units/{$unit->public_id}/discard"], ['POST', "$b/inventory/storage/{$location->public_id}/temperature"],
            ['GET', "$b/requests/{$request->public_id}"], ['POST', "$b/requests/{$request->public_id}/sample"], ['POST', "$b/requests/{$request->public_id}/reserve"], ['POST', "$b/requests/{$request->public_id}/issue"],
            ['POST', "$b/requests/{$request->public_id}/emergency"], ['POST', "$b/requests/{$request->public_id}/cancel"], ['POST', "$b/issues/{$issue->public_id}/return"], ['POST', "$b/issues/{$issue->public_id}/start"],
            ['GET', "$b/transfusions/{$transfusion->public_id}"], ['POST', "$b/transfusions/{$transfusion->public_id}/observations"], ['POST', "$b/transfusions/{$transfusion->public_id}/complete"], ['POST', "$b/transfusions/{$transfusion->public_id}/reaction"],
            ['POST', "$b/incidents/{$incident->public_id}/close"], ['GET', "$b"], ['GET', "$b/donors"], ['GET', "$b/inventory"], ['GET', "$b/requests"], ['GET', "$b/transfusions"], ['GET', "$b/incidents"], ['GET', "$b/lookback"], ['GET', "$b/reports"],
            ['POST', "$b/requests/mtp"], ['POST', "$b/recall"],
        ];
    }

    public function test_another_organization_reaches_nothing_and_a_member_without_permission_is_refused_everywhere(): void
    {
        $routes = $this->routes();

        $otherOrg = Organization::factory()->create();
        $otherBranch = Branch::factory()->create(['organization_id' => $otherOrg->id]);
        $this->actingAs($this->staff(['super_admin'], $otherOrg, $otherBranch));
        $this->context($otherOrg, $otherBranch);
        $leaks = [];
        foreach ($routes as [$method, $uri]) {
            $status = $this->call($method, $uri, [], [], [], ['HTTP_ACCEPT' => 'application/json'])->getStatusCode();
            // pages of the other organization's own (empty) module may open; a record of ours may not
            if (str_contains($uri, '/admin/blood-bank/') && preg_match('#/[0-9A-Z]{26}#', $uri) && ! in_array($status, [403, 404], true)) {
                $leaks[] = "$method $uri → $status";
            }
        }
        $this->assertSame([], $leaks);

        $this->seed(PermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($this->staff([]));
        $this->context();
        $open = [];
        foreach ($routes as [$method, $uri]) {
            $status = $this->call($method, $uri, [], [], [], ['HTTP_ACCEPT' => 'application/json'])->getStatusCode();
            if ($status !== 403) {
                $open[] = "$method $uri → $status";
            }
        }
        $this->assertSame([], $open);
    }

    public function test_a_module_viewer_can_look_but_not_change(): void
    {
        $this->seed(PermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(DemoAccess::class)->build($this->organization, $this->branch, 'x-pass-123');
        $viewer = User::where('email', 'bloodbank.viewer@'.strtolower($this->organization->code).'.example.invalid')->firstOrFail();
        $this->actingAs($viewer);

        $this->get('/admin/blood-bank')->assertOk();
        $this->get('/admin/blood-bank/inventory')->assertOk();
        $this->post('/admin/blood-bank/requests', ['patient' => 'x', 'component' => 'prbc', 'units_requested' => 1, 'priority' => 'routine'])->assertForbidden();
        $this->post('/admin/blood-bank/recall', ['donor' => 'x', 'reason' => 'x'])->assertForbidden();
    }
}
