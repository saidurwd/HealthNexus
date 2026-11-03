<?php

namespace Tests\Feature\Portal;

use App\Models\Crm\CrmCampaign;
use App\Models\Crm\CrmComplaint;
use App\Models\Crm\CrmFeedback;
use App\Models\Crm\CrmForm;
use App\Models\Crm\CrmFormSubmission;
use App\Models\Crm\CrmInteraction;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientPortalAccount;
use App\Models\Portal\PortalProxy;
use App\Models\Portal\PortalRefillRequest;
use App\Models\Portal\PortalReleasePolicy;
use App\Models\Portal\PortalResultRelease;
use App\Models\Portal\PortalServiceRequest;
use App\Models\Portal\PortalSettlement;
use App\Models\Portal\PortalThread;
use App\Models\User;
use App\Services\Crm\CampaignService;
use App\Services\Crm\ComplaintService;
use App\Services\Crm\FormService;
use App\Services\Crm\SegmentService;
use App\Services\Portal\CommunicationService;
use App\Services\Portal\DocumentShareService;
use App\Services\Portal\MessagingService;
use App\Services\Portal\PortalAudit;
use App\Services\Portal\PortalPaymentService;
use App\Services\Portal\PortalPolicy;
use App\Services\Portal\ProxyService;
use App\Services\Portal\RefillService;
use App\Services\Portal\ResultReleaseService;
use App\Services\Portal\ServiceRequestService;
use App\Services\TenantContextResolver;

class PortalDeskTest extends PortalTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->superAdmin();
        $this->actingAs($this->admin);
        session()->put(['tenant_organization_id' => $this->organization->id, 'tenant_branch_id' => $this->branch->id]);
    }

    /** One of everything, so every screen has something to show. */
    private function seedEverything(): void
    {
        $patient = $this->patient(['phone' => '01712345678']);
        $account = $this->account($patient);
        $this->labReport($patient);
        app(ResultReleaseService::class)->sync();
        $rx = $this->prescription($patient);
        app(RefillService::class)->request($account, $patient, $rx, 'low', 'k1');
        app(MessagingService::class)->start($account, $patient, 'Question', 'general', 'chest pain at night');
        app(ServiceRequestService::class)->create($account, $patient, 'records_copy', 'for insurer');
        $child = $this->patient(['date_of_birth' => now()->subYears(6)->toDateString()]);
        app(ProxyService::class)->request($account, $child->enterprise_patient_no, $child->date_of_birth->toDateString(), 'parent', ['billing'], 'birth record seen');
        $invoice = $this->invoice($patient);
        $pay = app(PortalPaymentService::class)->initiate($account, $patient, $invoice, 'bkash', 'k');
        app(PortalPaymentService::class)->confirm($pay);
        app(PortalPaymentService::class)->reconcile($this->organization->id, 'sandbox', '2026-09-01', '2026-09-30', [['gateway_ref' => $pay->gateway_ref, 'amount' => '1000.00']], $this->admin);
        app(CommunicationService::class)->send(['organization_id' => $this->organization->id, 'patient_id' => $patient->id, 'channel' => 'sms', 'purpose' => 'service', 'template' => 'x', 'to' => '8801712345678', 'body' => 'hello']);
        app(PortalAudit::class)->access($account, $patient->id, 'view_record');
        app(DocumentShareService::class)->share($patient->id, 'prescription', $rx->id, $account);

        $seg = app(SegmentService::class);
        $segment = $seg->activate($seg->create($this->organization->id, $this->admin, 'Everyone', []), $this->actor(['crm.segment.approve']));
        $campaigns = app(CampaignService::class);
        $campaign = $campaigns->create($this->organization->id, $this->admin, ['name' => 'Camp', 'purpose' => 'service', 'channel' => 'inapp', 'segment_id' => $segment->id, 'body' => 'Hi {name}']);
        $campaigns->approve($campaigns->submit($campaign), $this->actor(['crm.campaign.approve']));
        $campaigns->run($campaign->fresh());
        $complaint = app(ComplaintService::class)->lodge($this->organization->id, ['category' => 'billing', 'description' => 'double charge'], $this->admin, $patient);
        app(ComplaintService::class)->investigate($complaint, $this->admin, 'looking');
        CrmInteraction::create(['organization_id' => $this->organization->id, 'patient_id' => $patient->id, 'channel' => 'call', 'direction' => 'in', 'subject' => 'Asked about MRI', 'status' => 'open', 'follow_up_on' => today(), 'created_by' => $this->admin->id]);
        CrmFeedback::create(['organization_id' => $this->organization->id, 'patient_id' => $patient->id, 'rating' => 2, 'nps' => 3, 'comment' => 'Long wait', 'channel' => 'portal']);
        $form = app(FormService::class)->define($this->organization->id, 'pre-visit', 'Before your visit', [['key' => 'smoker', 'label' => 'Smoker?', 'type' => 'yesno', 'required' => true]]);
        app(FormService::class)->submit($form, $patient, $account, ['smoker' => 'no']);
    }

    public function test_every_staff_screen_opens_with_data_in_it(): void
    {
        $this->seedEverything();
        $release = PortalResultRelease::query()->first();
        $account = PatientPortalAccount::query()->first();
        $thread = PortalThread::query()->first();
        $proxy = PortalProxy::query()->first();
        $complaint = CrmComplaint::query()->first();
        $settlement = PortalSettlement::query()->first();
        $campaign = CrmCampaign::query()->first();
        $submission = CrmFormSubmission::query()->first();

        $urls = [
            'admin.portal.dashboard' => [], 'admin.portal.accounts' => [], 'admin.portal.accounts.show' => [$account->public_id], 'admin.portal.proxies' => [], 'admin.portal.outbox' => [], 'admin.portal.access-log' => [],
            'admin.portal.settings' => [], 'admin.portal.releases' => [], 'admin.portal.policies' => [], 'admin.portal.refills' => [], 'admin.portal.threads' => [], 'admin.portal.threads.show' => [$thread->public_id],
            'admin.portal.requests' => [], 'admin.portal.payments' => [], 'admin.portal.settlements.show' => [$settlement->public_id],
            'admin.crm.dashboard' => [], 'admin.crm.interactions' => [], 'admin.crm.feedback' => [], 'admin.crm.segments' => [], 'admin.crm.campaigns' => [], 'admin.crm.campaigns.show' => [$campaign->public_id],
            'admin.crm.complaints' => [], 'admin.crm.complaints.show' => [$complaint->public_id], 'admin.crm.forms' => [], 'admin.crm.forms.submission' => [$submission->public_id],
        ];
        foreach ($urls as $name => $params) {
            $this->get(route($name, $params))->assertOk();
        }
        $this->assertNotNull($release);
        $this->assertNotNull($proxy);
    }

    public function test_a_user_without_the_permission_is_refused_everywhere(): void
    {
        $this->seedEverything();
        $nobody = $this->actor([]);
        $this->actingAs($nobody);
        foreach (['admin.portal.dashboard', 'admin.portal.accounts', 'admin.portal.releases', 'admin.portal.refills', 'admin.portal.threads', 'admin.portal.proxies', 'admin.portal.payments', 'admin.portal.settings', 'admin.crm.dashboard', 'admin.crm.complaints', 'admin.crm.segments', 'admin.crm.campaigns'] as $name) {
            $this->get(route($name))->assertForbidden();
        }
    }

    public function test_staff_cannot_open_another_hospitals_records(): void
    {
        $other = Organization::factory()->create();
        $patient = Patient::factory()->create(['organization_id' => $other->id]);
        $account = PatientPortalAccount::factory()->create(['patient_id' => $patient->id, 'organization_id' => $other->id]);

        $this->get(route('admin.portal.accounts.show', $account->public_id))->assertNotFound();
        $this->post(route('admin.portal.accounts.status', $account->public_id), ['status' => 'suspended'])->assertNotFound();
        $this->assertSame('active', $account->fresh()->status);
    }

    public function test_the_release_queue_releases_and_keeps_back_and_refuses_a_critical_one_until_acknowledged(): void
    {
        $patient = $this->patient();
        $this->labReport($patient);
        $this->labReport($patient, 'Potassium', true);
        $this->get(route('admin.portal.releases'))->assertOk()->assertSee(__('Critical'));
        [$normal, $critical] = [PortalResultRelease::query()->where('critical', false)->first(), PortalResultRelease::query()->where('critical', true)->first()];

        $this->post(route('admin.portal.releases.decide', $normal->public_id), ['decision' => 'release'])->assertRedirect();
        $this->assertSame('released', $normal->fresh()->status);

        $this->from(route('admin.portal.releases'))->post(route('admin.portal.releases.decide', $critical->public_id), ['decision' => 'release'])->assertRedirect(route('admin.portal.releases'))->assertSessionHas('error');
        $this->assertSame('held_critical', $critical->fresh()->status);
        $this->post(route('admin.portal.releases.decide', $normal->public_id), ['decision' => 'withhold'])->assertSessionHasErrors('reason');
    }

    public function test_the_desk_makes_an_invitation_and_shows_the_code_once(): void
    {
        $patient = $this->patient();
        $res = $this->post(route('admin.portal.accounts.invite'), ['patient_no' => $patient->enterprise_patient_no])->assertRedirect(route('admin.portal.accounts'));
        $code = session('portal_invitation_code');
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/', $code);
        $this->get(route('admin.portal.accounts'))->assertSee($code);

        $this->post(route('admin.portal.accounts.invite'), ['patient_no' => 'EPT-NOPE'])->assertSessionHasErrors('patient_no');
        $this->assertNotNull(PatientPortalAccount::query()->where('patient_id', $patient->id)->first()->invitation_hash);
    }

    public function test_staff_can_lock_a_login_and_it_stops_working_at_once(): void
    {
        $account = $this->account();
        $headers = $this->bearer($account);
        $this->post(route('admin.portal.accounts.status', $account->public_id), ['status' => 'suspended', 'reason' => 'misuse'])->assertRedirect();
        $this->getJson('/api/v1/portal/me', $headers)->assertUnauthorized();
    }

    public function test_a_staff_member_replies_and_the_patient_sees_it(): void
    {
        $patient = $this->patient();
        $account = $this->account($patient);
        $headers = $this->bearer($account);
        $id = $this->postJson('/api/v1/portal/threads', ['subject' => 'Timing', 'body' => 'Is the clinic open on Friday?'], $headers)->json('data.id');
        $this->actingAs($this->admin);

        $this->post(route('admin.portal.threads.reply', $id), ['body' => 'Yes, 9 to 1.'])->assertRedirect();
        $this->getJson("/api/v1/portal/threads/$id", $headers)->assertOk()->assertJsonPath('data.status', 'answered')->assertJsonPath('data.messages.1.text', 'Yes, 9 to 1.');
    }

    public function test_the_other_staff_actions_work_through_the_screens(): void
    {
        $this->seedEverything();
        $proxy = PortalProxy::query()->first();
        $this->post(route('admin.portal.proxies.decide', $proxy->public_id), ['decision' => 'approve', 'note' => 'seen birth record'])->assertRedirect();
        $this->assertSame('active', $proxy->fresh()->status);

        $refill = PortalRefillRequest::query()->first();
        $this->post(route('admin.portal.refills.decide', $refill->public_id), ['decision' => 'approved'])->assertRedirect();
        $this->assertSame('approved', $refill->fresh()->status);

        $req = PortalServiceRequest::query()->first();
        $this->post(route('admin.portal.requests.update', $req->public_id), ['status' => 'done', 'resolution' => 'ready'])->assertRedirect();
        $this->assertSame('done', $req->fresh()->status);

        $this->post(route('admin.portal.settings.save'), ['release_default_mode' => 'delayed', 'release_default_delay_hours' => 12, 'quiet_start' => '22:00', 'quiet_end' => '07:00', 'max_marketing_per_week' => 1, 'link_ttl_days' => 3, 'change_cutoff_hours' => 6, 'min_lead_hours' => 1, 'max_failures' => 4, 'lock_minutes' => 30, 'terms_version' => '2'])->assertRedirect();
        $this->assertSame('delayed', app(PortalPolicy::class)->get('release.default_mode'));
        $this->assertSame('22:00', app(PortalPolicy::class)->get('communication.quiet_hours')['start']);

        $this->post(route('admin.portal.policies.save'), ['scope_type' => 'lab_category', 'scope_key' => 'HISTO', 'mode' => 'never', 'sensitive' => 1])->assertRedirect();
        $this->assertSame('never', PortalReleasePolicy::query()->where('scope_key', 'HISTO')->value('mode'));
    }

    public function test_the_crm_actions_work_through_the_screens(): void
    {
        $this->seedEverything();
        $complaint = CrmComplaint::query()->first();
        $this->post(route('admin.crm.complaints.action', $complaint->public_id), ['action' => 'resolve', 'note' => 'Refunded'])->assertRedirect();
        $this->post(route('admin.crm.complaints.action', $complaint->public_id), ['action' => 'close'])->assertRedirect();
        $this->assertSame('closed', $complaint->fresh()->status);

        $this->post(route('admin.crm.interactions.store'), ['channel' => 'call', 'subject' => 'Billing question', 'contact_name' => 'Mr Rahim', 'follow_up_on' => today()->addDay()->toDateString()])->assertRedirect();
        $this->assertSame(2, CrmInteraction::query()->count());

        $this->post(route('admin.crm.segments.store'), ['name' => 'Women over 40', 'age_from' => 40, 'sex' => 'F'])->assertRedirect();
        $this->post(route('admin.crm.forms.store'), ['code' => 'intake', 'title' => 'Intake', 'fields' => "pain | Where is the pain? | select | y | head,chest\nweight | Weight | number | n"])->assertRedirect();
        $this->assertSame(2, CrmForm::query()->count());
        $this->post(route('admin.crm.forms.review', CrmFormSubmission::query()->first()->public_id))->assertRedirect();
    }

    public function test_a_wrong_state_shows_the_message_on_the_page_not_a_crash(): void
    {
        $this->seedEverything();
        $campaign = CrmCampaign::query()->first();
        $this->from(route('admin.crm.campaigns.show', $campaign->public_id))->post(route('admin.crm.campaigns.action', $campaign->public_id), ['action' => 'run'])->assertRedirect()->assertSessionHas('error');
    }

    public function test_the_tenant_context_does_not_leak_between_hospitals_for_a_patient_login(): void
    {
        $other = Organization::factory()->create();
        app(TenantContextResolver::class)->setOrganizationId($other->id); // a staff session for another hospital is open in the same process
        $patient = $this->patient();
        $headers = $this->bearer($this->account($patient));

        $this->getJson('/api/v1/portal/me', $headers)->assertOk()->assertJsonPath('data.people.0.patient_no', $patient->enterprise_patient_no);
        $this->assertSame($this->organization->id, app(TenantContextResolver::class)->getOrganizationId() ?? $this->organization->id);
    }

    public function test_the_old_invite_button_on_the_patient_record_now_makes_a_one_time_code(): void
    {
        $patient = $this->patient();
        $this->post(route('admin.patients.portal.enable', $patient), ['email' => 'rina@example.org'])->assertRedirect(route('admin.patients.show', $patient))->assertSessionHas('portal_invitation_code');

        $account = PatientPortalAccount::query()->where('patient_id', $patient->id)->first();
        $this->assertSame('invited', $account->status);
        $this->assertSame('rina@example.org', $account->email);
        $this->assertNotNull($account->invitation_hash);
        $this->assertFalse($patient->fresh()->portal_enabled, 'the patient is "on the portal" only after they claim the code');
    }

    public function test_the_code_step_survives_a_page_refresh(): void
    {
        $patient = $this->patient(['phone' => '01712345678']);
        $this->post('/portal/register', ['identifier' => $patient->enterprise_patient_no, 'date_of_birth' => '1990-04-12', 'phone' => '01712345678']);
        $this->get('/portal/register/verify')->assertOk();
        $this->get('/portal/register/verify')->assertOk();
    }
}
