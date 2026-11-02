<?php

namespace Tests\Feature\Ai;

use App\Models\Ai\AiIncident;
use App\Models\Ai\AiOutput;
use App\Models\Ai\AiProvider;
use App\Models\Ai\AiRequest;
use App\Models\Ai\AiUseCase;
use App\Models\Organization;
use App\Services\Ai\AiBlocked;
use App\Services\Ai\AiGateway;
use App\Services\Ai\OutputService;
use App\Services\Ai\Providers\FakeProvider;
use App\Services\Ai\Providers\ProviderRequest;
use App\Services\Ai\ProviderService;
use App\Services\Ai\UseCases\TimelineSummary;
use App\Services\SettingsService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GatewayTest extends AiTestCase
{
    private function summarise($patient = null)
    {
        return app(TimelineSummary::class)->summarise($this->clinician, $patient ?? $this->chart());
    }

    public function test_the_local_engine_reads_the_record_back_with_every_line_cited_and_labelled_as_a_draft(): void
    {
        $o = $this->summarise();
        $this->assertSame(['summary', 'draft', 'AI-generated — needs review'], [$o->kind, $o->status, $o->label]);
        $this->assertStringContainsString('Allergy: Penicillin', $o->text);
        $this->assertStringContainsString('Type 2 diabetes mellitus', $o->text);
        $this->assertStringContainsString('BP 142/90', $o->text);
        foreach ($o->lines as $line) {
            $this->assertNotEmpty($line['refs'], 'every line cites a fact');
        }
        $this->assertNotEmpty($o->sources);
        $request = AiRequest::query()->find($o->request_id);
        $this->assertSame(['completed', 'identifiable', 'rules-extractive', 'timeline_summary'], [$request->outcome, $request->mode, $request->model_name, $request->use_case_code]);
        $this->assertNotEmpty($request->input, 'the inputs are kept');
        $this->assertSame($this->clinician->id, $o->requested_by);
    }

    public function test_a_request_is_refused_and_recorded_when_a_condition_is_not_met(): void
    {
        $patient = $this->chart();
        $svc = app(TimelineSummary::class);
        // the master switch
        app(SettingsService::class)->set('ai.enabled', 'false');
        try {
            $svc->summarise($this->clinician, $patient);
            $this->fail('AI is off');
        } catch (AiBlocked $e) {
            $this->assertSame('AI is switched off.', $e->getMessage());
        }
        app(SettingsService::class)->set('ai.enabled', 'true');
        // the function is paused
        AiUseCase::query()->where('code', 'timeline_summary')->update(['status' => 'paused']);
        try {
            $svc->summarise($this->clinician, $patient);
            $this->fail('paused');
        } catch (AiBlocked) {
        }
        AiUseCase::query()->where('code', 'timeline_summary')->update(['status' => 'enabled']);
        // the model is not approved / the provider is off
        DB::table('ai_models')->update(['status' => 'candidate']);
        try {
            $svc->summarise($this->clinician, $patient);
            $this->fail('model not approved');
        } catch (AiBlocked $e) {
            $this->assertStringContainsString('approved model', $e->getMessage());
        }
        DB::table('ai_models')->update(['status' => 'approved']);
        AiProvider::query()->update(['status' => 'disabled']);
        try {
            $svc->summarise($this->clinician, $patient);
            $this->fail('provider off');
        } catch (AiBlocked) {
        }
        $this->assertSame(4, AiRequest::query()->where('outcome', 'blocked')->count(), 'every refusal leaves a record');
        $this->assertSame(0, AiOutput::query()->count());
    }

    public function test_someone_without_the_right_or_from_another_hospital_gets_nothing(): void
    {
        $patient = $this->chart();
        $nobody = $this->actor(['patients.view']);
        $this->expectException(AuthorizationException::class);
        try {
            app(TimelineSummary::class)->summarise($nobody, $patient);
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $other = Organization::factory()->create();
            $stranger = $this->actor(['ai.use', 'patients.view']);
            $stranger->organizations()->sync([$other->id => ['access_level' => 'admin']]);
            try {
                app(TimelineSummary::class)->summarise($stranger, $patient);
            } catch (HttpException $e2) {
                $this->assertSame(403, $e2->getStatusCode());
                throw new AuthorizationException('ok');
            }
        }
    }

    public function test_only_this_patients_facts_are_in_the_context(): void
    {
        $mine = $this->chart();
        $other = $this->chart('Another patient: the pain is in the left arm.');
        $o = $this->summarise($mine);
        $refs = collect($o->sources)->pluck('ref');
        $allergyIds = DB::table('patient_allergies')->where('patient_id', $mine->id)->pluck('id');
        $this->assertTrue($allergyIds->every(fn ($id) => $refs->contains('allergy:'.$id)));
        $this->assertStringNotContainsString('left arm', $o->text);
        $this->assertSame([], collect($o->sources)->pluck('ref')->intersect(DB::table('patient_allergies')->where('patient_id', $other->id)->pluck('id')->map(fn ($i) => 'allergy:'.$i))->all());
    }

    public function test_a_provider_abroad_gets_only_de_identified_facts_until_a_legal_approval_is_recorded(): void
    {
        $patient = $this->chart('Rina Akter called from 01712345678 and said her pain is worse.');
        Http::fake(['https://ai.example.test/*' => Http::response(['choices' => [['message' => ['content' => json_encode(['lines' => []])]]], 'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 2]])]);
        putenv('TEST_AI_KEY=not-a-real-key');
        $providers = app(ProviderService::class);
        $p = $providers->add($this->organization->id, ['name' => 'Abroad model', 'kind' => 'http', 'location' => 'international', 'endpoint' => 'https://ai.example.test/v1', 'secret_env' => 'TEST_AI_KEY', 'max_level' => 'C4'], $this->clinician);
        $this->assertSame('C3', $p->max_level, 'a provider abroad is cleared for confidential data at most, until approved');
        $model = $providers->addModel($p, 'test-model', '1');
        $providers->setEnabled($p, true, $this->clinician);
        $model->update(['status' => 'approved']);
        AiUseCase::query()->where('code', 'timeline_summary')->update(['provider_id' => $p->id, 'model_id' => $model->id]);

        $this->summarise($patient);
        Http::assertSent(function ($request) {
            $body = json_encode($request->data(), JSON_UNESCAPED_UNICODE);
            $this->assertStringNotContainsString('Rina', $body);
            $this->assertStringNotContainsString('Akter', $body);
            $this->assertStringNotContainsString('01712345678', $body);
            $this->assertStringContainsString('[NAME]', $body);

            return $request->hasHeader('Authorization', 'Bearer not-a-real-key');
        });
        $this->assertSame('deidentified', AiRequest::query()->latest('id')->first()->mode);

        $providers->approveIdentifiable($p->refresh(), $this->actor(['ai.provider.legal']), 'LEGAL-2026-014 (written opinion)');
        $this->assertSame('identifiable', app(AiGateway::class)->mode($p->refresh()));
        $this->assertNull(DB::table('ai_providers')->where('id', $p->id)->value('secret_env') === 'not-a-real-key' ? 'leak' : null, 'the key itself is never stored');
        putenv('TEST_AI_KEY');
    }

    public function test_no_secret_value_is_accepted_in_place_of_its_name(): void
    {
        $this->expectException(ValidationException::class);
        app(ProviderService::class)->add($this->organization->id, ['name' => 'Sneaky', 'kind' => 'http', 'location' => 'bangladesh', 'endpoint' => 'https://x.example.test', 'secret_env' => 'sk-live-12345abcdef'], $this->clinician);
    }

    public function test_lines_that_cite_nothing_cite_foreign_facts_invent_numbers_or_tell_someone_to_act_are_dropped(): void
    {
        FakeProvider::script(fn (ProviderRequest $r) => [
            ['text' => 'The patient has a severe penicillin allergy.', 'refs' => [$r->items[0]['ref']], 'section' => 'Facts'],
            ['text' => 'The patient is probably a diabetic.', 'refs' => []],
            ['text' => 'Hemoglobin was 4.2.', 'refs' => ['lab:99999']],
            ['text' => 'Blood pressure was 190/120.', 'refs' => [$r->items[0]['ref']]],
            ['text' => 'You should prescribe amoxicillin 500 mg.', 'refs' => [$r->items[0]['ref']]],
            ['text' => 'Please discharge the patient today.', 'refs' => [$r->items[0]['ref']]],
            ['text' => 'ওষুধ দিন দ্রুত।', 'refs' => [$r->items[0]['ref']]],
        ]);
        AiUseCase::query()->update([]);
        DB::table('ai_providers')->update(['kind' => 'fake']);
        $o = $this->summarise();
        $this->assertSame(1, count($o->lines));
        $this->assertSame(6, $o->suppressed);
        $this->assertStringNotContainsString('amoxicillin', $o->text);
        $this->assertSame(1, AiIncident::query()->where('kind', 'unsafe_output')->count(), 'removing action lines opens an incident');
    }

    public function test_when_nothing_is_supported_the_answer_is_no_answer_not_a_guess(): void
    {
        FakeProvider::script(fn () => [['text' => 'Everything looks fine.', 'refs' => []]]);
        DB::table('ai_providers')->update(['kind' => 'fake']);
        $o = $this->summarise();
        $this->assertSame('none', $o->kind);
        $this->assertStringContainsString('No supported answer', $o->text);
        $this->expectException(ValidationException::class);
        app(OutputService::class)->accept($o, $this->clinician, $this->chart()->enterprise_patient_no);
    }

    public function test_a_provider_failure_leaves_a_record_and_no_output(): void
    {
        FakeProvider::script(function () {
            throw new \RuntimeException('connection refused');
        });
        DB::table('ai_providers')->update(['kind' => 'fake']);
        try {
            $this->summarise();
            $this->fail('the provider failed');
        } catch (AiBlocked $e) {
            $this->assertStringContainsString('Nothing was changed', $e->getMessage());
        }
        $this->assertSame(['failed'], AiRequest::query()->pluck('outcome')->all());
        $this->assertSame(0, AiOutput::query()->count());
    }

    public function test_the_request_record_cannot_be_changed_or_removed_even_by_sql(): void
    {
        $o = $this->summarise();
        foreach ([fn () => DB::table('ai_requests')->where('id', $o->request_id)->update(['outcome' => 'blocked']), fn () => DB::table('ai_requests')->where('id', $o->request_id)->delete(),
            fn () => DB::table('ai_outputs')->where('id', $o->id)->update(['text' => 'Rewritten']), fn () => DB::table('ai_outputs')->where('id', $o->id)->delete()] as $try) {
            try {
                $try();
                $this->fail('changed');
            } catch (\Throwable $e) {
                $this->assertMatchesRegularExpression('/only grows|cannot be changed|cannot be deleted/', $e->getMessage());
            }
        }
    }
}
