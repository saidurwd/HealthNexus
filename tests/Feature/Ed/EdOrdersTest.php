<?php

namespace Tests\Feature\Ed;

use App\Models\ClinicalOrder;
use App\Models\Ed\EdMedicationAdministration;
use App\Models\Ed\EdVisit;
use App\Models\Laboratory\LabOrder;
use App\Models\Prescription;
use App\Services\Ed\EdOrderService;
use App\Services\Ed\EdTriageService;
use App\Services\Ed\EdVisitService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

class EdOrdersTest extends EdTestCase
{
    private function visit(): EdVisit
    {
        return app(EdVisitService::class)->arrive($this->user, $this->branch, ['patient_id' => $this->patient()->id, 'arrival_mode' => 'walk_in']);
    }

    public function test_a_lab_order_is_a_clinical_order_on_the_ed_encounter_and_reaches_laboratory(): void
    {
        $visit = $this->visit();
        $order = app(EdOrderService::class)->placeOrder($visit, $this->user, 'laboratory', [['name' => 'CBC']]);

        $this->assertSame($visit->encounter_id, $order->encounter_id);
        $this->assertSame('routine', $order->priority);
        $this->assertSame(1, LabOrder::where('clinical_order_id', $order->id)->count());
    }

    public function test_urgent_triage_levels_send_orders_as_stat(): void
    {
        $visit = $this->visit();
        app(EdTriageService::class)->triage($visit, $this->user, ['level' => 2]);
        $order = app(EdOrderService::class)->placeOrder($visit->fresh(), $this->user, 'radiology', [['name' => 'Chest X-ray']]);

        $this->assertSame('stat', $order->priority);
    }

    public function test_a_prescription_is_issued_to_pharmacy(): void
    {
        $visit = $this->visit();
        $rx = app(EdOrderService::class)->prescribe($visit, $this->user, [['medicine_name' => 'Paracetamol', 'strength' => '500 mg', 'frequency' => 'TDS', 'duration' => '3 days']]);

        $this->assertSame('issued', $rx->status);
        $this->assertDatabaseHas('pharmacy_orders', ['prescription_id' => $rx->id]);
    }

    public function test_empty_orders_and_closed_visits_are_refused(): void
    {
        $visit = $this->visit();
        try {
            app(EdOrderService::class)->placeOrder($visit, $this->user, 'laboratory', [['name' => '']]);
            $this->fail('empty order accepted');
        } catch (ValidationException) {
        }

        $visit->update(['status' => EdVisit::DISPOSITIONED]);
        $this->expectException(ValidationException::class);
        app(EdOrderService::class)->placeOrder($visit, $this->user, 'laboratory', [['name' => 'CBC']]);
    }

    public function test_a_verbal_order_needs_the_doctor_named(): void
    {
        $visit = $this->visit();
        $service = app(EdOrderService::class);
        $data = ['drug_name' => 'Adrenaline', 'dose' => '1 mg', 'route' => 'IV', 'is_verbal_order' => true];
        try {
            $service->giveMedication($visit, $this->user, $data);
            $this->fail('verbal order without a doctor accepted');
        } catch (ValidationException) {
        }

        $service->giveMedication($visit, $this->user, $data + ['ordered_by' => $this->user->id]);
        $this->assertSame(1, EdMedicationAdministration::where('visit_id', $visit->id)->count());
    }

    public function test_screens_post_each_form_and_the_visit_page_lists_them(): void
    {
        $visit = $this->visit();
        $base = "/admin/emergency/visits/{$visit->public_id}";
        $this->post("$base/orders", ['order_type' => 'laboratory', 'items' => [['name' => 'CBC']]])->assertRedirect();
        $this->post("$base/prescriptions", ['items' => [['medicine_name' => 'Ondansetron', 'frequency' => 'once', 'duration' => '1 day']]])->assertRedirect();
        $this->post("$base/medications", ['drug_name' => 'Morphine', 'dose' => '2 mg', 'route' => 'IV'])->assertRedirect();
        $this->post("$base/procedures", ['procedure_name' => 'Suturing'])->assertRedirect();

        $this->assertSame(1, ClinicalOrder::where('encounter_id', $visit->encounter_id)->count());
        $this->assertSame(1, Prescription::where('encounter_id', $visit->encounter_id)->count());
        $this->get($base)->assertOk()->assertSee('Morphine')->assertSee('Suturing')->assertSee('Ondansetron');
    }

    public function test_a_nurse_cannot_place_orders_but_can_record_a_dose(): void
    {
        $visit = $this->visit();
        $nurse = $this->staff(['staff_nurse']);
        $this->seed(PermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($nurse);
        $base = "/admin/emergency/visits/{$visit->public_id}";

        $this->post("$base/orders", ['order_type' => 'laboratory', 'items' => [['name' => 'CBC']]])->assertForbidden();
        $this->post("$base/medications", ['drug_name' => 'Saline', 'dose' => '500 ml', 'route' => 'IV'])->assertRedirect();
    }
}
