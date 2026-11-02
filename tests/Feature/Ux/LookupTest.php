<?php

namespace Tests\Feature\Ux;

use App\Models\Patient;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\Feature\Dashboard\FrontDoorTestCase;

/** The searches behind the searchable dropdowns, and the forms that no longer ask anyone to type an internal ID. */
class LookupTest extends FrontDoorTestCase
{
    private function patient(array $o = []): Patient
    {
        return Patient::factory()->create($o + ['organization_id' => $this->organization->id]);
    }

    private function find(User $u, string $url): array
    {
        return $this->as($u)->getJson($url)->assertOk()->json();
    }

    private function user(): User
    {
        return $this->actor(['patients.view']);
    }

    private function superUser(): User
    {
        Role::findOrCreate('super_admin', 'web');
        $u = $this->actor([]);
        $u->assignRole('super_admin');

        return $u;
    }

    // ---- patients -----------------------------------------------------------------------------------------------------------------------------

    public function test_a_patient_is_found_by_name_number_phone_or_national_id_and_only_in_this_hospital(): void
    {
        $a = $this->patient(['first_name' => 'Abdul', 'last_name' => 'Karim', 'phone' => '01711000111', 'national_identifier' => '1990123456789']);
        $this->patient(['first_name' => 'Rahima', 'last_name' => 'Begum', 'phone' => '01822000222']);
        $other = $this->otherHospital();
        Patient::factory()->create(['organization_id' => $other->id, 'first_name' => 'Abdul', 'last_name' => 'Karim']);
        $u = $this->user();

        foreach (['abdul', 'karim', $a->enterprise_patient_no, '0171100', '19901234'] as $q) {
            $rows = $this->find($u, '/admin/lookup/patients?q='.urlencode($q));
            $this->assertSame([(string) $a->id], array_column($rows, 'value'), "found by [$q], and not the other hospital's namesake");
        }
        $text = $this->find($u, '/admin/lookup/patients?q=abdul')[0]['text'];
        $this->assertStringStartsWith('Abdul', $text);
        $this->assertStringEndsWith('('.$a->enterprise_patient_no.')', $text, 'the name, then the patient number');
        $this->assertSame(2, count($this->find($u, '/admin/lookup/patients')), 'nothing typed: the first few of this hospital');
    }

    public function test_every_word_must_match_and_the_exact_patient_number_comes_first_and_wildcards_are_plain_letters(): void
    {
        $this->patient(['first_name' => 'Rahim', 'last_name' => 'Uddin']);
        $both = $this->patient(['first_name' => 'Rahim', 'last_name' => 'Begum']);
        $pct = $this->patient(['first_name' => '100%', 'last_name' => 'Sure']);
        $u = $this->user();

        $this->assertSame([(string) $both->id], array_column($this->find($u, '/admin/lookup/patients?q='.urlencode('rahim begum')), 'value'));
        $this->assertSame([(string) $pct->id], array_column($this->find($u, '/admin/lookup/patients?q='.urlencode('%')), 'value'), '% is a character, not "everything"');
        $this->assertSame([], $this->find($u, '/admin/lookup/patients?q='.urlencode('_')), '_ is a character too');
        $first = $this->find($u, '/admin/lookup/patients?q='.urlencode($both->enterprise_patient_no));
        $this->assertSame((string) $both->id, $first[0]['value'], 'the patient with exactly that number first');
    }

    public function test_twenty_at_a_time_and_a_merged_patient_is_left_out(): void
    {
        Patient::factory()->count(25)->create(['organization_id' => $this->organization->id, 'first_name' => 'Same']);
        $keep = $this->patient(['first_name' => 'Zed']);
        $gone = $this->patient(['first_name' => 'Zed', 'merged_into_patient_id' => $keep->id]);

        $this->assertCount(20, $this->find($this->user(), '/admin/lookup/patients?q=same'));
        $this->assertSame([(string) $keep->id], array_column($this->find($this->user(), '/admin/lookup/patients?q=zed'), 'value'));
        $this->assertNotContains((string) $gone->id, array_column($this->find($this->user(), '/admin/lookup/patients?q=zed'), 'value'));
    }

    public function test_only_people_with_a_right_on_a_screen_that_picks_one_may_search(): void
    {
        $this->patient();
        $this->as($this->actor([]))->getJson('/admin/lookup/patients')->assertForbidden();
        foreach (['patients.view', 'icu.admission_request.view', 'ipd.admission.view', 'ot.surgery.view', 'billing.dashboard.view'] as $p) {
            $this->as($this->actor([$p]))->getJson('/admin/lookup/patients')->assertOk();
        }
        $this->app['auth']->forgetGuards();
        $this->getJson('/admin/lookup/patients')->assertUnauthorized();
    }

    // ---- encounters and admissions ------------------------------------------------------------------------------------------------------------

    public function test_visits_are_those_of_the_chosen_patient_newest_first_and_never_another_hospitals(): void
    {
        $p = $this->patient(['first_name' => 'Nasima']);
        $q = $this->patient(['first_name' => 'Other']);
        $old = $this->row('encounters', ['patient_id' => $p->id, 'encounter_no' => 'ENC-OLD', 'encounter_type' => 'OPD', 'started_at' => '2026-10-01 04:00:00']);
        $new = $this->row('encounters', ['patient_id' => $p->id, 'encounter_no' => 'ENC-NEW', 'encounter_type' => 'IPD', 'started_at' => '2026-11-10 04:00:00']);
        $this->row('encounters', ['patient_id' => $q->id, 'encounter_no' => 'ENC-Q', 'encounter_type' => 'OPD', 'started_at' => '2026-11-11 04:00:00']);
        $foreign = $this->patient(['organization_id' => $this->otherHospital()->id]);
        $this->row('encounters', ['organization_id' => $foreign->organization_id, 'patient_id' => $foreign->id, 'encounter_no' => 'ENC-F', 'encounter_type' => 'OPD', 'started_at' => '2026-11-12 04:00:00']);
        $u = $this->actor(['encounters.view']);

        $rows = $this->find($u, '/admin/lookup/encounters?patient_id='.$p->id);
        $this->assertSame([(string) $new, (string) $old], array_column($rows, 'value'));
        $this->assertSame('ENC-NEW · IPD · 10 Nov 2026', $rows[0]['text'], 'the patient is already chosen, so the label does not repeat the name');
        $this->assertSame(['ENC-Q'], array_map(fn ($r) => explode(' · ', $r['text'])[0], $this->find($u, '/admin/lookup/encounters?q=ENC-Q')), 'without a patient: by visit number, with the patient named');
        $this->assertStringContainsString('Other', $this->find($u, '/admin/lookup/encounters?q=ENC-Q')[0]['text']);
        $this->assertSame([], $this->find($u, '/admin/lookup/encounters?q=ENC-F'), 'another hospital\'s visit cannot be found');
        $this->assertSame([], $this->find($u, '/admin/lookup/encounters?patient_id='.$foreign->id), 'nor by another hospital\'s patient id');
    }

    public function test_admissions_are_those_of_the_chosen_patient_and_found_by_number(): void
    {
        $p = $this->patient(['first_name' => 'Rubina']);
        $a = $this->row('ipd_admissions', ['patient_id' => $p->id, 'admission_number' => 'ADM-77', 'encounter_id' => 1, 'admitted_at' => '2026-11-09 04:00:00', 'status' => 'admitted']);
        $this->row('ipd_admissions', ['patient_id' => $this->patient()->id, 'admission_number' => 'ADM-78', 'encounter_id' => 1, 'admitted_at' => '2026-11-09 04:00:00', 'status' => 'admitted']);
        $u = $this->actor(['ipd.admission.view']);

        $this->assertSame([(string) $a], array_column($this->find($u, '/admin/lookup/admissions?patient_id='.$p->id), 'value'));
        $this->assertSame('ADM-77 · admitted · 09 Nov 2026', $this->find($u, '/admin/lookup/admissions?patient_id='.$p->id)[0]['text']);
        $this->assertSame(['ADM-78'], array_map(fn ($r) => explode(' · ', $r['text'])[0], $this->find($u, '/admin/lookup/admissions?q=ADM-78')));
    }

    // ---- the forms ----------------------------------------------------------------------------------------------------------------------------

    public function test_no_form_asks_for_an_internal_id_any_more(): void
    {
        $left = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))) as $f) {
            if ($f->isFile() && str_ends_with($f->getFilename(), '.blade.php') && preg_match('/<input[^>]*name="(patient_id|encounter_id|admission_id)"[^>]*type="number"|<input[^>]*type="number"[^>]*name="(patient_id|encounter_id|admission_id)"/', (string) file_get_contents($f->getPathname()))) {
                $left[] = str_replace(resource_path('views').'/', '', $f->getPathname());
            }
        }
        $this->assertSame([], $left, 'a person finds a patient, visit or admission by searching, not by remembering a number');
    }

    public function test_the_icu_request_form_searches_for_the_patient_and_then_that_patients_visits(): void
    {
        $html = $this->as($this->superUser())->get('/admin/icu/admission-requests/create')->assertOk()->getContent();

        $this->assertStringNotContainsString('Patient ID', $html);
        $this->assertStringContainsString('name="patient_id"', $html);
        $this->assertStringContainsString('data-remote="'.route('admin.lookup.patients').'"', $html);
        $this->assertStringContainsString('data-remote="'.route('admin.lookup.encounters').'"', $html);
        $this->assertStringContainsString('data-remote-depends="[name=patient_id]"', $html, 'the visits narrow to the chosen patient');
        $this->assertStringContainsString('Type a name, patient number or phone', $html);
        $this->assertStringContainsString('Choose the patient first, then the visit', $html);
    }

    public function test_a_choice_made_before_a_form_error_comes_back_by_name_and_another_hospitals_patient_does_not(): void
    {
        $mine = $this->patient(['first_name' => 'Karima', 'last_name' => 'Akter']);
        $theirs = $this->patient(['organization_id' => $this->otherHospital()->id, 'first_name' => 'Stranger']);
        $user = $this->superUser();

        $html = $this->as($user)->withSession(['_old_input' => ['patient_id' => (string) $mine->id]])->get('/admin/icu/admission-requests/create')->getContent();
        $this->assertStringContainsString('Karima', $html);
        $this->assertStringContainsString($mine->enterprise_patient_no, $html);
        $this->assertStringContainsString('value="'.$mine->id.'" selected', $html);

        $html = $this->as($user)->withSession(['_old_input' => ['patient_id' => (string) $theirs->id]])->get('/admin/icu/admission-requests/create')->getContent();
        $this->assertStringNotContainsString('Stranger', $html);
        $this->assertStringNotContainsString('value="'.$theirs->id.'" selected', $html);
    }

    public function test_the_other_forms_use_the_same_pickers(): void
    {
        $user = $this->superUser();
        foreach (['/admin/ipd/admission-requests/create' => 1, '/admin/ot/surgery-requests/create' => 3, '/admin/billing/invoices/create' => 2, '/admin/billing/insurance/policies/create' => 1] as $url => $pickers) {
            $html = $this->as($user)->get($url)->assertOk()->getContent();
            $this->assertSame($pickers, substr_count($html, 'data-remote="'), "$url has $pickers searchable pickers");
            $this->assertStringNotContainsString('Patient ID', $html, $url);
        }
    }
}
