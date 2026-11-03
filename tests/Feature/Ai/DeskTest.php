<?php

namespace Tests\Feature\Ai;

use App\Models\Ai\AiOutput;
use App\Models\Ai\AiProvider;
use App\Models\Ai\AiUseCase;
use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContextResolver;
use Illuminate\Support\Facades\DB;

class DeskTest extends AiTestCase
{
    private function as(User $user): static
    {
        $this->actingAs($user);
        session()->put(['tenant_organization_id' => $this->organization->id, 'tenant_branch_id' => $this->branch->id]);
        app(TenantContextResolver::class)->setOrganizationId($this->organization->id);

        return $this;
    }

    public function test_every_screen_opens_for_the_super_user_and_is_refused_without_rights(): void
    {
        $this->as($this->superAdmin());
        foreach (['', '/summary', '/notes', '/ask', '/figures', '/forecast', '/outputs', '/use-cases', '/providers', '/prompts', '/knowledge', '/evaluations', '/incidents', '/monitoring', '/audit'] as $p) {
            $this->get('/ai'.$p)->assertOk();
        }
        $nobody = $this->actor([]);
        $this->as($nobody);
        foreach (['', '/summary', '/ask', '/outputs', '/use-cases', '/providers', '/prompts', '/knowledge', '/evaluations', '/incidents', '/monitoring', '/audit'] as $p) {
            $this->get('/ai'.$p)->assertForbidden();
        }
    }

    public function test_the_screens_for_the_four_higher_risk_functions_open_and_run_only_once_switched_on(): void
    {
        $patient = $this->chart();
        DB::table('vital_signs')->insert(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id, 'recorded_at' => now()->subHour(), 'temperature' => 37.0, 'temperature_unit' => 'celsius', 'systolic' => 118, 'diastolic' => 76,
            'pulse_rate' => 84, 'respiratory_rate' => 18, 'oxygen_saturation' => 97, 'created_at' => now(), 'updated_at' => now()]);
        $this->as($this->superAdmin());
        foreach (['/claim-check', '/reading-order', '/medicines', '/early-warning'] as $p) {
            $this->get('/ai'.$p)->assertOk();
        }
        $this->as($this->clinician)->from('/ai/early-warning')->post('/ai/early-warning', ['patient_no' => $patient->enterprise_patient_no])->assertSessionHas('warning'); // off: refused, nothing drafted
        $this->assertSame(0, AiOutput::query()->where('use_case_code', 'risk_scoring')->count());

        $this->enableFunction('risk_scoring');
        $this->enableFunction('medication_review');
        $this->as($this->clinician)->post('/ai/early-warning', ['patient_no' => $patient->enterprise_patient_no])->assertRedirect();
        $this->as($this->clinician)->post('/ai/medicines', ['patient_no' => $patient->enterprise_patient_no])->assertRedirect();
        $this->assertSame(1, AiOutput::query()->where('use_case_code', 'risk_scoring')->count());
        $this->assertSame(1, AiOutput::query()->where('use_case_code', 'medication_review')->count());
        $this->as($this->clinician)->post('/ai/medicines', ['patient_no' => 'NOPE-1'])->assertSessionHasErrors('patient_no');
        $this->as($this->actor([]))->get('/ai/early-warning')->assertForbidden();
        $this->as($this->clinician)->get('/ai')->assertOk()->assertSee('Early-warning screen')->assertDontSee('Suggest a reading order');
    }

    public function test_a_clinician_asks_reviews_and_accepts_through_the_screens_and_nothing_is_written_to_the_record(): void
    {
        $patient = $this->chart();
        $before = [DB::table('encounter_notes')->count(), DB::table('diagnoses')->count(), DB::table('prescriptions')->count(), DB::table('patient_allergies')->count()];
        $this->as($this->clinician)->post('/ai/summary', ['patient_no' => $patient->enterprise_patient_no])->assertRedirect();
        $o = AiOutput::query()->first();
        $this->as($this->clinician)->get('/ai/outputs/'.$o->public_id)->assertOk()->assertSee('AI-generated — needs review')->assertSee('Penicillin')->assertSee($patient->enterprise_patient_no);
        $this->as($this->clinician)->post('/ai/outputs/'.$o->public_id.'/accept', ['patient_no' => 'WRONG-1'])->assertSessionHasErrors('patient_no');
        $this->as($this->clinician)->post('/ai/outputs/'.$o->public_id.'/accept', ['patient_no' => $patient->enterprise_patient_no, 'edited_text' => 'My reviewed version'])->assertSessionHas('success');
        $this->assertSame('accepted', $o->fresh()->status);
        $this->assertSame($before, [DB::table('encounter_notes')->count(), DB::table('diagnoses')->count(), DB::table('prescriptions')->count(), DB::table('patient_allergies')->count()], 'accepting writes nothing to the record');
        $this->as($this->clinician)->get('/ai/outputs/'.$o->public_id)->assertOk()->assertSee('My reviewed version');
    }

    public function test_someone_who_may_use_ai_but_not_review_cannot_accept_and_others_cannot_see_the_draft(): void
    {
        $patient = $this->chart();
        $this->as($this->clinician)->post('/ai/summary', ['patient_no' => $patient->enterprise_patient_no]);
        $o = AiOutput::query()->first();
        $nurse = $this->actor(['ai.use', 'patients.view']);
        $this->as($nurse)->get('/ai/outputs/'.$o->public_id)->assertForbidden();
        $reviewer = $this->actor(['ai.use', 'ai.output.review', 'patients.view']);
        $this->as($reviewer)->get('/ai/outputs/'.$o->public_id)->assertOk();
        $nurse2 = $this->actor(['ai.use']);
        $this->as($nurse2)->post('/ai/outputs/'.$o->public_id.'/accept', ['patient_no' => $patient->enterprise_patient_no])->assertForbidden();
        $this->as($this->clinician)->post('/ai/summary', ['patient_no' => 'NO-SUCH'])->assertSessionHasErrors('patient_no');
    }

    public function test_the_kill_switch_stops_everything_and_asking_then_says_why(): void
    {
        $patient = $this->chart();
        $lead = $this->actor(['ai.killswitch', 'ai.dashboard.view', 'ai.use']);
        $this->as($lead)->post('/ai/kill-switch', ['state' => 'off'])->assertSessionHas('warning');
        $this->as($lead)->post('/ai/kill-switch', ['state' => 'off', 'reason' => 'Investigating a wrong summary'])->assertSessionHas('warning', 'AI is switched off for the whole hospital.');
        $this->as($this->clinician)->post('/ai/summary', ['patient_no' => $patient->enterprise_patient_no])->assertSessionHas('warning', 'AI is switched off.');
        $this->assertSame(0, AiOutput::query()->count());
        $this->as($this->clinician)->get('/ai')->assertOk()->assertSee('AI is switched off for the whole hospital');
        $this->as($lead)->post('/ai/kill-switch', ['state' => 'on'])->assertSessionHas('success');
        $this->as($this->clinician)->post('/ai/summary', ['patient_no' => $patient->enterprise_patient_no])->assertRedirect();
        $this->assertSame(1, AiOutput::query()->count());
    }

    public function test_governance_screens_follow_the_rights(): void
    {
        $viewer = $this->actor(['ai.usecase.view']);
        $this->as($viewer)->get('/ai/use-cases')->assertOk();
        $uc = AiUseCase::query()->where('code', 'risk_scoring')->first();
        $this->as($viewer)->post('/ai/use-cases/'.$uc->public_id.'/enable')->assertForbidden();
        $approver = $this->actor(['ai.usecase.approve']);
        $this->as($approver)->post('/ai/use-cases/'.$uc->public_id.'/enable')->assertSessionHasErrors('status');
        $this->assertSame('planned', $uc->fresh()->status);
        $p = $this->actor(['ai.provider.manage']);
        $this->as($p)->post('/ai/providers', ['name' => 'Cloud model', 'kind' => 'http', 'location' => 'international', 'endpoint' => 'https://ai.example.test/v1', 'secret_env' => 'MY_AI_KEY'])->assertSessionHas('success');
        $this->as($p)->post('/ai/providers', ['name' => 'Bad', 'kind' => 'http', 'location' => 'international', 'endpoint' => 'https://ai.example.test/v1', 'secret_env' => 'sk-live-abc123'])->assertSessionHasErrors('secret_env');
        $legal = AiProvider::query()->where('name', 'Cloud model')->first();
        $this->as($p)->post('/ai/providers/'.$legal->public_id.'/legal', ['legal_ref' => 'LEGAL-1 written opinion'])->assertForbidden();
        $this->as($this->actor(['ai.provider.legal']))->post('/ai/providers/'.$legal->public_id.'/legal', ['legal_ref' => 'LEGAL-1 written opinion'])->assertSessionHas('success');
        $this->assertTrue((bool) $legal->fresh()->allows_identifiable);
    }

    public function test_the_read_only_api_needs_a_login_the_right_and_the_header_and_cannot_ask_anything(): void
    {
        $patient = $this->chart();
        $this->as($this->clinician)->post('/ai/summary', ['patient_no' => $patient->enterprise_patient_no]);
        app('auth')->forgetGuards();
        foreach (['use-cases', 'outputs', 'metrics'] as $p) {
            $this->getJson("/api/v1/ai/$p")->assertUnauthorized();
        }
        $h = ['Authorization' => 'Bearer '.$this->clinician->createToken('t')->plainTextToken, 'X-Organization-Id' => (string) $this->organization->id];
        $this->assertCount(13, $this->getJson('/api/v1/ai/use-cases', $h)->assertOk()->json('data'));
        $out = $this->getJson('/api/v1/ai/outputs', $h)->assertOk();
        $this->assertSame(['summary', 'draft'], [$out->json('data.0.kind'), $out->json('data.0.status')]);
        $this->assertArrayNotHasKey('text', $out->json('data.0'), 'the text is not in the list');
        $this->getJson('/api/v1/ai/metrics', $h)->assertForbidden();
        $this->postJson('/api/v1/ai/outputs', [], $h)->assertStatus(405);
        $other = Organization::factory()->create();
        $this->getJson('/api/v1/ai/use-cases', ['X-Organization-Id' => (string) $other->id] + $h)->assertForbidden();
    }
}
