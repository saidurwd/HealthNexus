<?php

namespace Tests\Feature\Portal;

use App\Models\Encounter;
use App\Models\PatientAllergy;
use App\Services\Portal\PortalAuthService;
use Illuminate\Support\Facades\DB;

class PortalExtrasTest extends PortalTestCase
{
    public function test_stays_show_the_discharge_papers_only_once_the_stay_is_finished(): void
    {
        $patient = $this->patient();
        $headers = $this->bearer($this->account($patient));
        $user = $this->actor([]);
        $enc = Encounter::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id, 'encounter_no' => 'E-'.uniqid(), 'encounter_type' => 'ipd', 'provider_id' => $user->id, 'encounter_date' => now()->toDateString(), 'status' => 'completed', 'source' => 'walk_in', 'created_by' => $user->id]);
        foreach ([['IPD-1', 'admitted', null], ['IPD-2', 'discharged', now()->subDay()]] as [$no, $status, $out]) {
            $id = DB::table('ipd_admissions')->insertGetId(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'admission_number' => $no, 'patient_id' => $patient->id, 'encounter_id' => $enc->id, 'admitted_at' => now()->subDays(3), 'actual_discharge_date' => $out, 'status' => $status, 'priority' => 'routine', 'created_at' => now(), 'updated_at' => now()]);
            DB::table('ipd_discharge_requests')->insert(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'admission_id' => $id, 'patient_id' => $patient->id, 'discharge_type' => 'routine', 'planned_date' => now()->toDateString(), 'discharge_diagnosis' => 'Pneumonia', 'instructions' => 'Rest and finish the antibiotics', 'follow_up_date' => now()->addWeek()->toDateString(), 'status' => 'completed', 'created_at' => now(), 'updated_at' => now()]);
        }

        $stays = collect($this->getJson('/api/v1/portal/records/summary', $headers)->assertOk()->json('data.stays'))->keyBy('number');
        $this->assertNull($stays['IPD-1']['diagnosis'], 'nothing is told before the stay is finished');
        $this->assertNull($stays['IPD-1']['instructions']);
        $this->assertSame('Pneumonia', $stays['IPD-2']['diagnosis']);
        $this->assertSame('Rest and finish the antibiotics', $stays['IPD-2']['instructions']);
    }

    public function test_insurance_on_file_is_shown_with_the_number_masked(): void
    {
        $patient = $this->patient();
        $headers = $this->bearer($this->account($patient));
        $provider = DB::table('billing_insurance_providers')->insertGetId(['organization_id' => $this->organization->id, 'name' => 'Demo Insurer', 'code' => 'DEMO', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('billing_insurance_policies')->insert(['organization_id' => $this->organization->id, 'insurance_provider_id' => $provider, 'patient_id' => $patient->id, 'policy_number' => 'POL-123456789', 'status' => 'active', 'effective_from' => now()->subYear()->toDateString(), 'created_at' => now(), 'updated_at' => now()]);

        $res = $this->getJson('/api/v1/portal/records/summary', $headers)->assertOk();
        $this->assertSame('••••6789', $res->json('data.insurance.0.policy'));
        $this->assertStringNotContainsString('POL-123456789', $res->getContent());
    }

    public function test_the_medicines_page_reminds_about_allergies_and_the_sign_in_code_can_be_made_always_on(): void
    {
        $patient = $this->patient(['phone' => '01712345678']);
        $account = $this->account($patient);
        PatientAllergy::create(['organization_id' => $this->organization->id, 'patient_id' => $patient->id, 'substance' => 'Penicillin', 'severity' => 'severe', 'is_active' => true]);
        $this->actingAs($account, 'portal');
        $this->get('/portal/prescriptions')->assertOk()->assertSee('Penicillin');

        $this->post('/portal/settings/preferences', ['mfa' => '1'])->assertRedirect();
        $this->assertTrue($account->fresh()->mfa_required);

        app(PortalAuthService::class)->registerDevice($account->fresh(), 'known-1', 'Phone', 'android', false);
        $this->assertTrue(app(PortalAuthService::class)->attempt('01712345678', 'Portal-Pass-123', 'known-1')['otp_required'], 'a trusted phone still gets asked when the person chose so');
    }

    public function test_the_app_can_ask_for_a_language_on_any_call(): void
    {
        $account = $this->account($this->patient(), ['language' => 'en']);
        $headers = $this->bearer($account);

        $this->getJson('/api/v1/portal/preferences', $headers)->assertJsonPath('note', null)->assertJsonPath('meta.note', 'Messages about your own care and security cannot be switched off.');
        $this->getJson('/api/v1/portal/preferences', $headers + ['Accept-Language' => 'bn'])->assertJsonPath('meta.note', 'আপনার নিজের চিকিৎসা ও নিরাপত্তা সংক্রান্ত বার্তা বন্ধ করা যায় না।');
    }

    /** Accessibility basics on the pages a patient uses most: every field has a label, the language is declared, buttons have text, images have alt text. */
    public function test_patient_pages_label_every_field_and_declare_their_language(): void
    {
        $patient = $this->patient(['phone' => '01712345678']);
        $account = $this->account($patient);
        $this->slot($this->provider());
        $this->invoice($patient);
        $this->prescription($patient);
        $guestPages = ['/portal/login', '/portal/register', '/portal/activate', '/portal/forgot'];
        $pages = ['/portal/home', '/portal/appointments', '/portal/appointments/book', '/portal/billing', '/portal/messages', '/portal/family', '/portal/settings', '/portal/prescriptions', '/portal/requests', '/portal/complaints', '/portal/records', '/portal/help'];
        foreach (array_merge($guestPages, $pages) as $n => $page) {
            if ($n === count($guestPages)) {
                $this->actingAs($account, 'portal');
            }
            $html = $this->get($page)->assertOk()->getContent();
            $dom = new \DOMDocument;
            @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
            $xpath = new \DOMXPath($dom);
            $this->assertMatchesRegularExpression('/<html lang="(bn|en)"/', $html, "$page declares its language");
            foreach ($xpath->query('//input[not(@type="hidden") and not(@type="submit")]|//select|//textarea') as $field) {
                $id = $field->getAttribute('id');
                $labelled = ($id !== '' && $xpath->query("//label[@for='$id']")->length > 0) || $field->getAttribute('aria-label') !== '' || $field->parentNode->nodeName === 'label';
                $this->assertTrue($labelled, "$page: field ".($field->getAttribute('name') ?: $field->nodeName).' has no label');
            }
            foreach ($xpath->query('//button') as $button) {
                $this->assertNotSame('', trim($button->textContent), "$page: a button has no text");
            }
            foreach ($xpath->query('//img') as $img) {
                $this->assertTrue($img->hasAttribute('alt'), "$page: an image has no alt text");
            }
            $this->assertSame(1, $xpath->query('//h1')->length, "$page has exactly one main heading");
        }
    }
}
