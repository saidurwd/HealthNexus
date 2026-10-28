<?php

namespace Tests\Feature\Portal;

use App\Models\Appointment;
use App\Models\Billing\BillingPayment;
use App\Models\Crm\CrmComplaint;
use App\Models\Crm\CrmFormSubmission;
use App\Models\PatientConsent;
use App\Models\PatientPortalAccount;
use App\Models\Portal\PortalPayment;
use App\Models\Portal\PortalProxy;
use App\Models\Portal\PortalResultRelease;
use App\Models\Portal\PortalThread;
use App\Services\Crm\FormService;
use App\Services\Portal\CommunicationService;
use App\Services\Portal\MessagingService;
use App\Services\Portal\PortalPresenter;
use App\Services\Portal\ResultReleaseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class PortalWebTest extends PortalTestCase
{
    private function signIn(?PatientPortalAccount $account = null): PatientPortalAccount
    {
        $account ??= $this->account($this->patient(['phone' => '01712345678']));
        $this->actingAs($account, 'portal');
        $this->withCookie('portal_device', 'dev-1');

        return $account;
    }

    public function test_guests_are_sent_to_the_login_page_and_staff_logins_do_not_open_patient_pages(): void
    {
        $this->get('/portal/home')->assertRedirect('/portal/login');
        $this->get('/portal/results')->assertRedirect('/portal/login');
        $this->actingAs($this->superAdmin()); // a staff login is not a patient login
        $this->get('/portal/home')->assertRedirect('/portal/login');
    }

    public function test_the_public_pages_open(): void
    {
        foreach (['/portal/login', '/portal/register', '/portal/activate', '/portal/forgot'] as $url) {
            $this->get($url)->assertOk()->assertSee('<form', false);
        }
        $this->get('/portal/login')->assertSee(config('app.name'));
    }

    public function test_signing_up_on_the_web_end_to_end(): void
    {
        $patient = $this->patient(['phone' => '01712345678']);
        $this->post('/portal/register', ['identifier' => $patient->enterprise_patient_no, 'date_of_birth' => '1990-04-12', 'phone' => '01712345678'])->assertRedirect('/portal/register/verify');
        $challenge = session('challenge');
        $this->get('/portal/register/verify')->assertOk();

        $this->post('/portal/register/verify', ['challenge' => $challenge, 'code' => $this->lastCode('8801712345678'), 'password' => 'Strong-Pass-1', 'password_confirmation' => 'Strong-Pass-1', 'accept_terms' => '1'])->assertRedirect('/portal/home');
        $this->assertTrue(Auth::guard('portal')->check());
        $this->get('/portal/home')->assertOk()->assertSee('Rina')->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_a_wrong_code_keeps_the_person_on_the_page_with_a_message(): void
    {
        $patient = $this->patient(['phone' => '01712345678']);
        $this->post('/portal/register', ['identifier' => $patient->enterprise_patient_no, 'date_of_birth' => '1990-04-12', 'phone' => '01712345678']);
        $challenge = session('challenge');

        $this->from('/portal/register/verify')->post('/portal/register/verify', ['challenge' => $challenge, 'code' => '000000', 'password' => 'Strong-Pass-1', 'password_confirmation' => 'Strong-Pass-1', 'accept_terms' => '1'])
            ->assertRedirect('/portal/register/verify')->assertSessionHas('error');
        $this->assertFalse(Auth::guard('portal')->check());
    }

    public function test_login_from_a_new_browser_asks_for_a_code_then_remembers_the_browser(): void
    {
        $this->account($this->patient(['phone' => '01712345678']));
        $this->withCookie('portal_device', 'browser-1');

        $this->post('/portal/login', ['phone' => '01712345678', 'password' => 'Portal-Pass-123'])->assertRedirect('/portal/login/code');
        $challenge = session('challenge');
        $this->post('/portal/login/code', ['challenge' => $challenge, 'code' => $this->lastCode('8801712345678')])->assertRedirect('/portal/home');

        Auth::guard('portal')->logout();
        $this->withCookie('portal_device', 'browser-1')->post('/portal/login', ['phone' => '01712345678', 'password' => 'Portal-Pass-123'])->assertRedirect('/portal/home');
    }

    public function test_wrong_password_shows_a_neutral_message(): void
    {
        $this->account($this->patient(['phone' => '01712345678']));
        $this->from('/portal/login')->post('/portal/login', ['phone' => '01712345678', 'password' => 'nope-nope-1'])->assertRedirect('/portal/login')->assertSessionHas('error', 'The phone number or password is not right.');
    }

    public function test_the_home_page_and_every_patient_page_open_with_data(): void
    {
        $patient = $this->patient(['phone' => '01712345678']);
        $account = $this->signIn($this->account($patient));
        $this->labReport($patient);
        app(ResultReleaseService::class)->sync();
        $release = PortalResultRelease::query()->first();
        app(ResultReleaseService::class)->release($release, $this->actor(['portal.release.manage']));
        $rx = $this->prescription($patient);
        $this->invoice($patient);
        $thread = app(MessagingService::class)->start($account, $patient, 'Hello', 'general', 'A question');
        $form = app(FormService::class)->define($this->organization->id, 'pre-visit', 'Before your visit', [['key' => 'smoker', 'label' => 'Smoker?', 'type' => 'yesno']]);
        $this->slot($this->provider());
        $done = Appointment::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id, 'appointment_date' => now()->subDay()->toDateString(), 'appointment_time' => '10:00', 'status' => 'completed']);

        foreach (['home', 'notifications', 'records', 'results', 'prescriptions', 'family', 'settings', 'appointments', 'appointments/book', 'billing', 'messages', 'requests', 'complaints', 'forms', 'help'] as $page) {
            $this->get("/portal/$page")->assertOk();
        }
        $this->get("/portal/results/{$release->public_id}")->assertOk()->assertSee('Hemoglobin')->assertSee('13.5');
        $this->get("/portal/messages/{$thread->public_id}")->assertOk()->assertSee('A question');
        $this->get('/portal/forms/pre-visit')->assertOk()->assertSee('Smoker?');
        $this->assertNotNull($rx);
        $this->assertNotNull($done);
        $this->assertNotNull($form);
    }

    public function test_the_results_page_never_shows_a_withheld_result(): void
    {
        $patient = $this->patient();
        $this->signIn($this->account($patient));
        $this->labReport($patient, 'Secret test');
        app(ResultReleaseService::class)->sync();

        $this->get('/portal/results')->assertOk()->assertDontSee('Secret test')->assertSee('not shown here yet');
        $this->get('/portal/results/'.PortalResultRelease::query()->first()->public_id)->assertNotFound();
    }

    public function test_booking_changing_and_cancelling_an_appointment_on_the_web(): void
    {
        $patient = $this->patient();
        $this->signIn($this->account($patient));
        $provider = $this->provider('Dr. Salma');
        $slot = $this->slot($provider, '+2 days 10:00');
        $doctor = PortalPresenter::provider($provider)['id'];

        $this->get('/portal/appointments/book?doctor='.urlencode($doctor).'&date='.now()->addDays(2)->toDateString())->assertOk()->assertSee('Dr. Salma');
        $this->post('/portal/appointments/book', ['slot' => Crypt::encryptString((string) $slot->id), 'reason' => 'Checkup'])->assertRedirect('/portal/appointments');
        $appointment = Appointment::query()->first();
        $this->assertSame($patient->id, $appointment->patient_id);

        $next = $this->slot($provider, '+3 days 10:00');
        $this->post('/portal/appointments/book', ['slot' => Crypt::encryptString((string) $next->id), 'reschedule' => $appointment->public_id])->assertRedirect('/portal/appointments');
        $this->assertSame('cancelled', $appointment->fresh()->status);
        $new = Appointment::query()->where('status', 'scheduled')->first();
        $this->post("/portal/appointments/{$new->public_id}/cancel")->assertRedirect('/portal/appointments');
        $this->assertSame('cancelled', $new->fresh()->status);
    }

    public function test_paying_on_the_web_goes_to_the_provider_and_the_return_page_confirms_it_with_the_provider(): void
    {
        $patient = $this->patient();
        $this->signIn($this->account($patient));
        $invoice = $this->invoice($patient, '700.00');

        $this->post("/portal/billing/{$invoice->public_id}/pay", ['method' => 'bkash', 'key' => 'web-1'])->assertOk()->assertSee('Go to the payment page');
        $payment = PortalPayment::query()->first();
        $this->assertSame(0, BillingPayment::query()->count());

        $this->get("/portal/pay/return/{$payment->public_id}")->assertRedirect('/portal/billing')->assertSessionHas('success');
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->get('/portal/billing')->assertOk()->assertSee('Printable copy');
    }

    public function test_messages_requests_complaints_and_forms_on_the_web(): void
    {
        $patient = $this->patient();
        $this->signIn($this->account($patient));
        $form = app(FormService::class)->define($this->organization->id, 'pre-visit', 'Before your visit', [['key' => 'smoker', 'label' => 'Smoker?', 'type' => 'yesno', 'required' => true]]);

        $this->post('/portal/messages', ['subject' => 'Fever', 'body' => 'My child has a seizure'])->assertRedirect()->assertSessionHas('error');
        $this->assertTrue(PortalThread::query()->first()->urgent);
        $this->post('/portal/requests', ['kind' => 'records_copy', 'details' => 'for insurer'])->assertRedirect();
        $this->post('/portal/complaints', ['category' => 'waiting_time', 'description' => 'Too long'])->assertRedirect();
        $this->assertSame(1, CrmComplaint::query()->count());
        $this->post('/portal/forms/pre-visit', ['answers' => ['smoker' => 'no']])->assertRedirect('/portal/forms');
        $this->assertSame(1, CrmFormSubmission::query()->count());
        $this->post('/portal/forms/pre-visit', ['answers' => []])->assertSessionHasErrors('smoker');
        $this->assertNotNull($form);
    }

    public function test_a_relative_can_be_chosen_only_if_approved_and_pages_then_show_that_person(): void
    {
        $parent = $this->account($this->patient(['phone' => '01712345678']));
        $child = $this->patient(['first_name' => 'Tiny', 'date_of_birth' => now()->subYears(6)->toDateString()]);
        $this->signIn($parent);
        $this->post('/portal/switch', ['for' => $child->public_id])->assertStatus(403);

        PortalProxy::create(['organization_id' => $this->organization->id, 'account_id' => $parent->id, 'patient_id' => $child->id, 'relationship' => 'parent', 'scopes' => ['appointments', 'billing'], 'status' => 'active', 'valid_from' => now()->toDateString(), 'valid_to' => now()->addYear()->toDateString()]);
        $this->post('/portal/switch', ['for' => $child->public_id])->assertRedirect('/portal/home');
        $invoice = $this->invoice($child);
        $this->get('/portal/billing')->assertOk()->assertSee($invoice->invoice_number);
        $this->get('/portal/results')->assertStatus(403);
    }

    public function test_language_can_be_switched_and_remembered_and_logout_ends_the_session(): void
    {
        $account = $this->signIn();
        $this->get('/portal/language/bn')->assertRedirect();
        $this->assertSame('bn', $account->fresh()->language);
        $this->get('/portal/language/xx')->assertNotFound();

        $this->post('/portal/logout')->assertRedirect('/portal/login');
        $this->assertFalse(Auth::guard('portal')->check());
    }

    public function test_settings_save_preferences_and_consents_and_the_password_changes(): void
    {
        $patient = $this->patient();
        $account = $this->signIn($this->account($patient));
        $this->post('/portal/settings/preferences', ['pref' => ['sms' => ['service' => '1', 'marketing' => '1']], 'consent' => ['research' => '1']])->assertRedirect();
        $this->assertTrue(app(CommunicationService::class)->isOptedIn($patient->id, 'sms', 'marketing'));
        $this->assertFalse(app(CommunicationService::class)->isOptedIn($patient->id, 'email', 'service'));
        $this->assertTrue(PatientConsent::query()->where(['patient_id' => $patient->id, 'consent_type' => 'research', 'status' => 'active'])->exists());

        $this->post('/portal/settings/password', ['current' => 'Portal-Pass-123', 'password' => 'Another-Pass-9', 'password_confirmation' => 'Another-Pass-9'])->assertRedirect();
        $this->assertTrue(Hash::check('Another-Pass-9', $account->fresh()->password));
    }
}
