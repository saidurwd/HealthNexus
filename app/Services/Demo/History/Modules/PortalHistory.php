<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Billing\BillingInvoice;
use App\Models\Crm\CrmComplaint;
use App\Models\Crm\CrmFeedback;
use App\Models\DoctorSchedule;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\PatientConsent;
use App\Models\PatientPortalAccount;
use App\Models\PatientProblem;
use App\Models\Portal\PortalPayment;
use App\Models\Portal\PortalProxy;
use App\Models\Portal\PortalRefillRequest;
use App\Models\Portal\PortalReleasePolicy;
use App\Models\Portal\PortalResultRelease;
use App\Models\Portal\PortalServiceRequest;
use App\Models\Portal\PortalThread;
use App\Models\Prescription;
use App\Models\User;
use App\Services\Appointments\ScheduleGenerationService;
use App\Services\Crm\CampaignService;
use App\Services\Crm\ComplaintService;
use App\Services\Crm\FeedbackService;
use App\Services\Crm\FormService;
use App\Services\Crm\InteractionService;
use App\Services\Crm\SegmentService;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use App\Services\Portal\CommunicationService;
use App\Services\Portal\DocumentShareService;
use App\Services\Portal\MessagingService;
use App\Services\Portal\PhoneNumber;
use App\Services\Portal\PortalAppointmentService;
use App\Services\Portal\PortalAuthService;
use App\Services\Portal\PortalException;
use App\Services\Portal\PortalPaymentService;
use App\Services\Portal\ProxyService;
use App\Services\Portal\RefillService;
use App\Services\Portal\ResultReleaseService;
use App\Services\Portal\ServiceRequestService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The patient portal and patient relations, six months. A share of the patients who were seen join the portal after a visit (some at the front desk with an invitation code, some
 * on their own). Every day: new reports get their release decision and clinicians release or keep back; patients write messages (a few look urgent), staff answer; patients ask for refills,
 * pay open bills through the (sandbox) payment provider, rate visits, fill in forms and sometimes complain. Parents get approved to act for children. Each month the provider's settlement
 * file is matched (with one amount that differs and one payment the file forgot). Relations staff log calls, run two campaigns a month through a group that was approved by a second person
 * (only people who said yes get the marketing one) and work complaints to the end — two are left late on purpose. Everything goes through the module's own services.
 */
class PortalHistory implements DemoHistoryModule
{
    public const PASSWORD = 'Portal-Demo-1';

    private const SUBJECTS = [
        ['Timing of my next visit', 'appointment', 'Can I move my follow-up to next week? I have an exam on the day.'],
        ['Question about my bill', 'billing', 'I was charged for a test I did not have. Please check.'],
        ['Report not showing', 'results', 'My blood test was done two days ago but I cannot see the report.'],
        ['Medicine side effect', 'prescription', 'The new tablet makes me sleepy in the day. Is that usual?'],
        ['Need a copy of my papers', 'general', 'I need my discharge papers for the insurance office.'],
        ['Thank you', 'general', 'Please thank the nurses on ward 3. They were very kind.'],
        ['Parking and visiting hours', 'general', 'What are the visiting hours for the cabin block?'],
        ['Repeat prescription', 'prescription', 'I am running out of my blood pressure tablets. Can I get a refill?'],
    ];

    private const URGENT = ['I have severe chest pain since last night and I am scared.', 'My father is bleeding from a wound and it will not stop.'];

    private const COMPLAINTS = [
        ['waiting_time', 'medium', 'I waited more than two hours in the outpatient area before I was called.'],
        ['billing', 'medium', 'The bill had a charge for a service I did not receive.'],
        ['staff_behaviour', 'high', 'The person at the registration desk was rude to my elderly mother.'],
        ['cleanliness', 'low', 'The washroom on the second floor was dirty.'],
        ['facility', 'low', 'The lift to the pathology floor has been out of order for days.'],
        ['clinical_care', 'high', 'Nobody explained the plan for my husband\'s treatment when he was admitted.'],
        ['privacy', 'medium', 'My test report was read out loud in the waiting area.'],
    ];

    private const COMMENTS = ['Doctor listened carefully.', 'Long wait but a good consultation.', 'Clean and well organised.', 'Staff were helpful.', 'The waiting time was too long.', 'I could not find parking.', 'Very satisfied.'];

    /** @var array<string, int> */
    private array $summary = [];

    /** @var array<string, list<array<string, mixed>>> work for a later day, by date */
    private array $later = [];

    /** @var list<array{kind:string,phone:string,patient:string,patient_no:string}> */
    private array $logins = [];

    public function __construct(
        private readonly PortalAuthService $auth,
        private readonly ResultReleaseService $release,
        private readonly MessagingService $messages,
        private readonly RefillService $refills,
        private readonly PortalAppointmentService $appointments,
        private readonly ProxyService $proxies,
        private readonly CommunicationService $comms,
        private readonly DocumentShareService $documents,
        private readonly ServiceRequestService $requests,
        private readonly FeedbackService $feedback,
        private readonly ComplaintService $complaints,
        private readonly InteractionService $interactions,
        private readonly SegmentService $segments,
        private readonly CampaignService $campaigns,
        private readonly FormService $forms,
        private readonly ScheduleGenerationService $schedules,
    ) {}

    public function name(): string
    {
        return 'Patient portal and CRM';
    }

    public function module(): string
    {
        return 'portal';
    }

    public function run(HistoryContext $context): string
    {
        config(['portal.payments.driver' => 'sandbox']); // a pretend payment provider; nothing real is charged
        $org = $context->organization->id;
        $doctor = $context->staff('doctor')->first();
        $officers = $context->staff('patient_relations_officer');
        $agents = $context->staff('call_center_agent');
        $managers = $context->staff('patient_relations_manager');
        $manager = $managers->first(fn ($u) => $u->id !== $officers->first()->id) ?? $managers->first();
        $this->setup($context, $doctor);

        $adopters = $this->chooseAdopters($context);
        $joinOn = [];
        foreach ($adopters as $row) {
            $joinOn[$row['joins']->toDateString()][] = $row;
        }

        /** @var array<int, PatientPortalAccount> $accounts patient id => account */
        $accounts = [];
        $this->summary = ['logins' => 0, 'desk_invites' => 0, 'threads' => 0, 'urgent' => 0, 'refills' => 0, 'payments' => 0, 'ratings' => 0, 'forms' => 0, 'complaints' => 0, 'calls' => 0, 'requests' => 0, 'shared' => 0, 'campaigns' => 0, 'settlements' => 0];
        $form = $this->forms->define($org, 'pre-visit', 'Before your visit', [
            ['key' => 'reason', 'label' => 'What is the main reason for your visit?', 'type' => 'textarea', 'required' => true],
            ['key' => 'smoker', 'label' => 'Do you smoke?', 'type' => 'yesno', 'required' => true],
            ['key' => 'allergy', 'label' => 'Any new allergy?', 'type' => 'yesno'],
            ['key' => 'pain', 'label' => 'Pain level', 'type' => 'select', 'options' => ['none', 'mild', 'moderate', 'severe']],
        ]);
        $days = iterator_to_array($context->days());
        $monthEnds = [];

        foreach ($days as $i => $day) {
            $date = $day->toDateString();
            // morning (11:00 in Dhaka): held texts go out, work promised for today is done, people join
            $context->at($day->setTime(5, 0), function () use ($context, $date, &$accounts, $joinOn, $doctor, $officers) {
                $this->comms->releaseDeferred();
                foreach ($this->later[$date] ?? [] as $task) {
                    $this->finish($task, $context, $doctor, $officers, $accounts);
                }
                foreach ($joinOn[$date] ?? [] as $row) {
                    try {
                        $accounts[$row['patient']->id] = $this->join($row, $context);
                    } catch (PortalException) {
                        // a person the portal rightly refuses (for example deceased since) simply never joins
                    }
                }
            });
            if ($accounts === []) {
                continue;
            }
            // midday: patients do things
            $context->at($day->setTime(7, 0), function () use ($context, $day, $date, &$accounts, $form) {
                $ids = array_keys($accounts);
                foreach ($this->sample($ids, max(1, (int) round(count($ids) * 0.05))) as $pid) {
                    $this->writeMessage($accounts[$pid], $date);
                }
                foreach ($this->sample($ids, max(0, (int) round(count($ids) * 0.02))) as $pid) {
                    $this->askRefill($accounts[$pid], $date);
                }
                foreach ($this->sample($ids, max(0, (int) round(count($ids) * 0.06))) as $pid) {
                    $this->payOnline($accounts[$pid], $day);
                }
                foreach ($this->sample($ids, max(0, (int) round(count($ids) * 0.04))) as $pid) {
                    $this->rate($accounts[$pid]);
                }
                if ($context->chance(25)) {
                    $this->fillForm($accounts[$ids[mt_rand(0, count($ids) - 1)]], $form);
                }
                if ($context->chance(8)) {
                    $account = $accounts[$ids[mt_rand(0, count($ids) - 1)]];
                    $this->requests->create($account, $account->patient, $context->pick(['records_copy', 'report_reprint', 'insurance_update', 'address_change']), 'Needed for my insurance office.');
                    $this->summary['requests']++;
                    $this->later[CarbonImmutable::parse($date)->addDays(mt_rand(1, 4))->toDateString()][] = ['type' => 'request'];
                }
                if ($context->chance(12)) {
                    $this->complain($context, $accounts[$ids[mt_rand(0, count($ids) - 1)]], $date);
                }
                if ($context->chance(6)) {
                    $account = $accounts[$ids[mt_rand(0, count($ids) - 1)]];
                    $this->failedLogins($account);
                }
            });
            // relations staff: calls
            $context->at($day->setTime(6, 0), function () use ($context, $date, $agents, &$accounts) {
                $count = mt_rand(0, 3);
                for ($n = 0; $n < $count; $n++) {
                    $patient = $context->chance(70) ? $accounts[array_rand($accounts)]->patient : null;
                    $this->interactions->log($context->organization->id, $context->pick($agents), [
                        'patient_id' => $patient?->id, 'contact_name' => $patient ? null : 'Caller '.mt_rand(100, 999), 'contact_phone' => $patient ? null : '0171'.mt_rand(1000000, 9999999),
                        'channel' => $context->pick(['call', 'call', 'walk_in', 'sms', 'email']), 'direction' => $context->chance(70) ? 'in' : 'out', 'subject' => $context->pick(['Asked about doctor availability', 'Asked about test prices', 'Wants a copy of a report', 'Reported a billing problem', 'Asked how to join the portal', 'Follow-up after discharge']),
                        'summary' => 'Demo contact.', 'follow_up_on' => $context->chance(45) ? CarbonImmutable::parse($date)->addDays(mt_rand(1, 5))->toDateString() : null, 'status' => 'done',
                    ]);
                    $this->summary['calls']++;
                }
            });
            // evening (18:00 in Dhaka): new reports get a decision; clinicians release
            $context->at($day->setTime(12, 0), function () use ($org, $doctor, $day, $context) {
                $this->release->sync($org, 500);
                $this->release->releaseDue();
                $cut = $day->subDay()->setTime(23, 59);
                PortalResultRelease::query()->where('organization_id', $org)->whereIn('status', ['pending', 'held_critical'])->where('reported_at', '<=', $cut)->orderBy('id')->limit(60)->get()->each(function (PortalResultRelease $row) use ($doctor, $context) {
                    try {
                        if ($context->chance(88)) {
                            $this->release->release($row, $doctor);
                        } elseif ($row->status === 'pending' && $context->chance(40)) {
                            $this->release->withhold($row, $doctor, 'The doctor will explain this at the next visit.');
                        }
                    } catch (PortalException) {
                        // a critical result whose alert nobody acknowledged stays held back — on purpose
                    }
                });
            });
            if ($day->isSameDay($day->endOfMonth()) || $i === array_key_last($days)) {
                $monthEnds[] = $day;
            }
        }

        $end = $context->to->setTime(13, 0);
        $context->at($end, function () use ($context, $monthEnds, $accounts, $officers, $manager, $doctor) {
            foreach ($monthEnds as $monthEnd) {
                $this->settle($monthEnd, $context);
            }
            $this->proxiesAndDocuments($context, $accounts, $officers, $doctor);
            $this->campaignsRun($context, $officers, $manager);
            $this->bookAhead($context, $accounts);
            $this->showcase($context, $accounts);
            $this->payments()->sweep();
            $this->complaints->escalateOverdue();
        });

        $context->share('portal_logins', $this->logins);

        return sprintf('%d patient logins (%d from the front desk), %d messages (%d urgent), %d refill requests, %d online payments, %d ratings, %d forms, %d complaints, %d contacts logged, %d campaigns, %d settlement files',
            $this->summary['logins'], $this->summary['desk_invites'], $this->summary['threads'], $this->summary['urgent'], $this->summary['refills'], $this->summary['payments'], $this->summary['ratings'], $this->summary['forms'], $this->summary['complaints'], $this->summary['calls'], $this->summary['campaigns'], $this->summary['settlements']);
    }

    // ---- set-up -------------------------------------------------------------------------------------------------------------------------------------

    private function setup(HistoryContext $context, User $doctor): void
    {
        $org = $context->organization->id;
        $rules = [['default', 'all', 'after_review', 0, false, 'Everything waits for a clinician unless a rule below says otherwise.'], ['radiology', 'all', 'after_review', 0, false, null]];
        foreach (DB::table('lab_test_categories')->where('organization_id', $org)->orderBy('id')->limit(6)->get(['code', 'name']) as $n => $cat) {
            $rules[] = ['lab_category', (string) $cat->code, $n % 3 === 0 ? 'immediate' : ($n % 3 === 1 ? 'delayed' : 'after_review'), $n % 3 === 1 ? 24 : 0, false, 'Demo rule for '.$cat->name];
        }
        foreach ($rules as [$type, $key, $mode, $delay, $sensitive, $note]) {
            PortalReleasePolicy::query()->updateOrCreate(['organization_id' => $org, 'scope_type' => $type, 'scope_key' => $key], ['mode' => $mode, 'delay_hours' => $delay, 'sensitive' => $sensitive, 'note' => $note, 'updated_by' => $doctor->id]);
        }
    }

    /** @return list<array{patient:Patient,joins:CarbonImmutable,desk:bool}> */
    private function chooseAdopters(HistoryContext $context): array
    {
        $first = Encounter::query()->where('organization_id', $context->organization->id)->selectRaw('patient_id, min(encounter_date) as first_visit')->groupBy('patient_id')->get()->keyBy('patient_id');
        $patients = Patient::query()->where('organization_id', $context->organization->id)->whereIn('id', $first->keys())->whereNotNull('date_of_birth')->whereNull('deceased_at')->whereNull('merged_into_patient_id')->get()
            ->filter(fn (Patient $p) => $p->date_of_birth->age >= 16)->values();
        $target = max(12, (int) round($patients->count() * 0.45));
        $usedPhones = [];
        $out = [];
        foreach ($patients->shuffle()->take($target) as $p) {
            $phone = PhoneNumber::normalize($p->phone);
            if ($phone === null || isset($usedPhones[$phone])) {
                $phone = PhoneNumber::normalize('0170'.str_pad((string) (1000000 + $p->id), 7, '0', STR_PAD_LEFT));
                $p->forceFill(['phone' => $phone])->save();
            }
            $usedPhones[$phone] = true;
            $when = CarbonImmutable::parse((string) $first[$p->id]->getAttribute('first_visit'))->addDays(mt_rand(0, 4));
            $when = $when->lt($context->from) ? $context->from->addDays(mt_rand(0, 6)) : $when;
            if ($when->gt($context->to->subDays(1))) {
                $when = $context->to->subDays(mt_rand(1, 3));
            }
            $out[] = ['patient' => $p, 'joins' => $when, 'desk' => mt_rand(1, 100) <= 30];
        }

        return $out;
    }

    /** @param array{patient:Patient,joins:CarbonImmutable,desk:bool} $row */
    private function join(array $row, HistoryContext $context): PatientPortalAccount
    {
        $patient = $row['patient'];
        $dob = $patient->date_of_birth->toDateString();
        if ($row['desk']) {
            [, $code] = $this->auth->invite($patient, $context->actor->id);
            $account = $this->auth->claimInvitation($code, $dob, self::PASSWORD, true);
            $this->summary['desk_invites']++;
        } else {
            $start = $this->auth->startRegistration(['identifier' => $patient->enterprise_patient_no, 'date_of_birth' => $dob, 'phone' => (string) $patient->phone]);
            $otp = DB::table('portal_outbox')->where('template', 'otp')->where('to', PhoneNumber::normalize($patient->phone))->orderByDesc('id')->value('body');
            preg_match('/\d{6}/', (string) $otp, $m);
            $account = $this->auth->completeRegistration($start['challenge'], $m[0] ?? '000000', self::PASSWORD, true);
        }
        $account->forceFill(['language' => mt_rand(1, 100) <= 70 ? 'bn' : 'en'])->save();
        $this->auth->registerDevice($account, 'demo-'.$account->id, mt_rand(1, 100) <= 60 ? 'Android phone' : 'Browser', mt_rand(1, 100) <= 60 ? 'android' : 'web', false);
        if (mt_rand(1, 100) <= 35) {
            $this->comms->setPreference($patient, 'inapp', 'marketing', true);
            $this->comms->setPreference($patient, 'sms', 'marketing', true);
        }
        if (mt_rand(1, 100) <= 10) {
            $this->comms->setPreference($patient, 'sms', 'service', false);
        }
        $this->summary['logins']++;

        return $account->refresh();
    }

    // ---- what patients do -----------------------------------------------------------------------------------------------------------------------------

    private function writeMessage(PatientPortalAccount $account, string $date): void
    {
        $urgent = mt_rand(1, 100) <= 4;
        [$subject, $category, $body] = $urgent ? ['Need help', 'general', self::URGENT[mt_rand(0, 1)]] : self::SUBJECTS[mt_rand(0, count(self::SUBJECTS) - 1)];
        $thread = $this->messages->start($account, $account->patient, $subject, $category, $body);
        $this->summary['threads']++;
        $urgent && $this->summary['urgent']++;
        $this->later[CarbonImmutable::parse($date)->addDays($urgent ? 0 : mt_rand(0, 2))->toDateString()][] = ['type' => 'reply', 'thread' => $thread->id, 'account' => $account->id, 'closes' => mt_rand(1, 100) <= 70];
    }

    private function askRefill(PatientPortalAccount $account, string $date): void
    {
        $rx = Prescription::query()->where('patient_id', $account->patient_id)->whereNotNull('issued_at')->where('issued_at', '>=', now()->subDays(80))->where('issued_at', '<=', now())->inRandomOrder()->first();
        if (! $rx) {
            return;
        }
        try {
            $row = $this->refills->request($account, $account->patient, $rx, 'Running low.', 'demo-'.$rx->id.'-'.$date);
            $this->summary['refills']++;
            $this->later[CarbonImmutable::parse($date)->addDays(mt_rand(1, 2))->toDateString()][] = ['type' => 'refill', 'id' => $row->id];
        } catch (PortalException) {
        }
    }

    private function payOnline(PatientPortalAccount $account, CarbonImmutable $day): void
    {
        $invoice = BillingInvoice::query()->where('patient_id', $account->patient_id)->whereIn('status', ['finalized', 'partially_paid'])->whereDate('invoice_date', '<=', $day->toDateString())->orderBy('id')->first();
        if (! $invoice || bccomp($invoice->remainingDue(), '10', 2) < 0) {
            return;
        }
        try {
            $method = ['bkash', 'nagad', 'card'][mt_rand(0, 2)];
            $part = mt_rand(1, 100) <= 20 ? bcdiv($invoice->remainingDue(), '2', 2) : null;
            $payment = $this->payments()->initiate($account, $account->patient, $invoice, $method, 'demo-'.$invoice->id.'-'.$day->toDateString(), $part);
            $this->payments()->confirm($payment);
            $this->summary['payments']++;
        } catch (PortalException|ValidationException) {
        }
    }

    private function rate(PatientPortalAccount $account): void
    {
        $visit = Appointment::query()->where('patient_id', $account->patient_id)->where('status', 'completed')->whereDate('appointment_date', '>=', now()->subDays(10)->toDateString())->whereDate('appointment_date', '<=', now()->toDateString())
            ->whereNotIn('id', CrmFeedback::query()->where('patient_id', $account->patient_id)->whereNotNull('appointment_id')->select('appointment_id'))->first();
        if (! $visit) {
            return;
        }
        $rating = [5, 5, 5, 4, 4, 4, 3, 2, 1][mt_rand(0, 8)];
        try {
            $this->feedback->submit($account->patient, $visit, $rating, max(0, min(10, $rating * 2 - mt_rand(0, 1))), self::COMMENTS[mt_rand(0, count(self::COMMENTS) - 1)]);
            $this->summary['ratings']++;
        } catch (PortalException) {
        }
    }

    private function fillForm(PatientPortalAccount $account, $form): void
    {
        $this->forms->submit($form, $account->patient, $account, ['reason' => 'Regular check-up and medicine review.', 'smoker' => mt_rand(0, 4) === 0 ? 'yes' : 'no', 'allergy' => 'no', 'pain' => ['none', 'mild', 'moderate'][mt_rand(0, 2)]]);
        $this->summary['forms']++;
    }

    private function complain(HistoryContext $context, PatientPortalAccount $account, string $date): void
    {
        [$category, $severity, $text] = self::COMPLAINTS[mt_rand(0, count(self::COMPLAINTS) - 1)];
        $c = $this->complaints->lodge($context->organization->id, ['category' => $category, 'severity' => $severity, 'description' => $text, 'channel' => 'portal'], null, $account->patient);
        $this->summary['complaints']++;
        $this->later[CarbonImmutable::parse($date)->addDays(1)->toDateString()][] = ['type' => 'complaint-1', 'id' => $c->id, 'date' => $date];
    }

    private function failedLogins(PatientPortalAccount $account): void
    {
        try {
            $this->auth->attempt((string) $account->phone, 'not-the-password-1', 'unknown-device');
        } catch (PortalException) {
        }
    }

    // ---- staff side, later in the day -----------------------------------------------------------------------------------------------------------------

    /** @param array<string, mixed> $task */
    private function finish(array $task, HistoryContext $context, User $doctor, $officers, array $accounts): void
    {
        $officer = $context->pick($officers);
        try {
            switch ($task['type']) {
                case 'reply':
                    $thread = PortalThread::query()->find($task['thread']);
                    $this->messages->staffReply($thread, $officer, $thread->urgent ? 'Please call the emergency number or come to the emergency department now. We cannot help with urgent problems by message.' : 'Thank you for writing. We have looked into this and the front desk will contact you if anything more is needed.');
                    if (mt_rand(1, 100) <= 30) {
                        $account = PatientPortalAccount::query()->find($task['account']);
                        $this->messages->patientReply($account, $thread->fresh(), 'Thank you.');
                        $this->messages->staffReply($thread->fresh(), $officer, 'You are welcome.');
                    }
                    $task['closes'] && $this->messages->close($thread->fresh());
                    break;
                case 'refill':
                    $row = PortalRefillRequest::query()->find($task['id']);
                    $row && $this->refills->decide($row, $doctor, ['approved', 'approved', 'approved', 'needs_visit', 'declined'][mt_rand(0, 4)], null);
                    break;
                case 'request':
                    $row = PortalServiceRequest::query()->where('organization_id', $context->organization->id)->whereIn('status', ['requested', 'in_progress'])->orderBy('id')->first();
                    $row && $this->requests->update($row, $officer, mt_rand(1, 100) <= 90 ? 'done' : 'rejected', 'Ready at the records desk.');
                    break;
                case 'complaint-1':
                    $c = CrmComplaint::query()->find($task['id']);
                    $this->complaints->assign($c, $officer, $officer);
                    $this->complaints->investigate($c->fresh(), $officer, 'Spoke to the department head.');
                    $this->later[CarbonImmutable::parse($task['date'])->addDays(mt_rand(2, 6))->toDateString()][] = ['type' => 'complaint-2', 'id' => $c->id];
                    break;
                case 'complaint-2':
                    $c = CrmComplaint::query()->find($task['id']);
                    if ($c->due_at && $c->due_at->lt(now()->subDays(3)) && mt_rand(1, 100) <= 8) {
                        break; // left late on purpose
                    }
                    $this->complaints->resolve($c, $officer, 'We looked into this, spoke to the people involved and changed the way it is done. We are sorry.');
                    if (mt_rand(1, 100) <= 60) {
                        $this->complaints->satisfaction($c->fresh(), [5, 4, 4, 3, 2][mt_rand(0, 4)]);
                    }
                    $this->complaints->close($c->fresh(), $context->pick($officers));
                    break;
            }
        } catch (PortalException|ValidationException) {
        }
    }

    // ---- month end and the finishing touches ----------------------------------------------------------------------------------------------------------

    private function settle(CarbonImmutable $monthEnd, HistoryContext $context): void
    {
        $from = $monthEnd->startOfMonth()->max($context->from);
        $paid = PortalPayment::query()->where('organization_id', $context->organization->id)->where('status', 'succeeded')->whereNull('reconciled_at')->whereBetween(DB::raw('date(completed_at)'), [$from->toDateString(), $monthEnd->toDateString()])->orderBy('id')->get();
        if ($paid->isEmpty()) {
            return;
        }
        $lines = [];
        foreach ($paid->values() as $n => $p) {
            if ($n === 1 && $paid->count() > 3) {
                continue; // the provider's file forgot this one
            }
            $amount = $n === 2 && $paid->count() > 3 ? bcsub((string) $p->amount, '1.00', 2) : (string) $p->amount; // this one differs by a taka
            $lines[] = ['gateway_ref' => $p->gateway_ref, 'amount' => $amount, 'fee' => bcmul((string) $p->amount, '0.015', 2), 'paid_on' => $p->completed_at?->toDateString()];
        }
        $this->payments()->reconcile($context->organization->id, 'sandbox', $from->toDateString(), $monthEnd->toDateString(), $lines, $context->actor, 'settlement-'.$monthEnd->format('Y-m').'.csv');
        $this->summary['settlements']++;
    }

    private function proxiesAndDocuments(HistoryContext $context, array $accounts, $officers, User $doctor): void
    {
        $org = $context->organization->id;
        $children = Patient::query()->where('organization_id', $org)->whereDate('date_of_birth', '>', now()->subYears(12)->toDateString())->limit(6)->get();
        $parents = collect($accounts)->values();
        foreach ($children as $n => $child) {
            $parent = $parents[$n % max(1, $parents->count())] ?? null;
            if (! $parent || $parent->patient_id === $child->id) {
                continue;
            }
            $this->proxies->request($parent, $child->enterprise_patient_no, $child->date_of_birth->toDateString(), 'parent', ['appointments', 'billing', 'prescriptions', 'results'], 'Birth record seen at the desk.');
            $row = PortalProxy::query()->where(['account_id' => $parent->id, 'patient_id' => $child->id])->latest('id')->first();
            if ($row && $n < 4) {
                $this->proxies->approve($row, $context->pick($officers), null, null, 'Checked the birth record.');
            }
        }
        // a few released reports are shared as printable links
        PortalResultRelease::query()->where('organization_id', $org)->where('status', 'released')->inRandomOrder()->limit(10)->get()->each(function (PortalResultRelease $r) use (&$accounts) {
            $account = $accounts[$r->patient_id] ?? null;
            if ($account) {
                $this->documents->share($r->patient_id, $r->source_type, $r->source_id, $account);
                $this->summary['shared']++;
            }
        });
    }

    private function campaignsRun(HistoryContext $context, $officers, ?User $approver): void
    {
        $org = $context->organization->id;
        $author = $officers->first();
        if (! $approver || $approver->id === $author->id) {
            $approver = User::query()->whereHas('organizations', fn ($q) => $q->where('organizations.id', $org))->where('id', '!=', $author->id)->whereHas('roles', fn ($q) => $q->whereIn('name', ['hospital_admin', 'super_admin']))->first();
        }
        if (! $approver) {
            return;
        }
        $lapsed = $this->segments->activate($this->segments->create($org, $author, 'Not seen for 60 days', ['last_visit_before_days' => 60], 'Patients we have not seen for two months'), $approver);
        $women = $this->segments->activate($this->segments->create($org, $author, 'Women 30 to 55', ['age_from' => 30, 'age_to' => 55, 'sex' => 'F'], 'For the health check camp'), $approver);
        $portal = $this->segments->activate($this->segments->create($org, $author, 'Portal users', ['has_portal' => true], 'Everyone with a portal login'), $approver);

        // a group about a diagnosis: legal basis written down, approved by a second person, only people who agreed
        $clinical = null;
        $prefix = PatientProblem::query()->where('organization_id', $org)->whereNotNull('problem_code')->selectRaw('substr(problem_code, 1, 3) p, count(*) c')->groupBy('p')->orderByDesc('c')->value('p');
        if ($prefix) {
            PatientProblem::query()->where('organization_id', $org)->where('problem_code', 'like', $prefix.'%')->limit(12)->pluck('patient_id')->unique()->each(function ($pid) use ($context) {
                PatientConsent::query()->firstOrCreate(['patient_id' => $pid, 'consent_type' => 'clinical_marketing', 'status' => 'active'], ['organization_id' => $context->organization->id, 'version' => 1, 'granted_at' => now(), 'notes' => 'Demo consent']);
            });
            $clinical = $this->segments->activate($this->segments->create($org, $author, "Condition {$prefix} education", ['problem_code_prefix' => $prefix], 'Patients who agreed to condition education messages', 'Education programme; consent form CF-12; approved by the medical director.'), $approver);
        }

        $months = [];
        foreach ($context->days() as $d) {
            $months[$d->format('Y-m')] = $d;
        }
        foreach (array_values($months) as $n => $monthDay) {
            $at = $monthDay->startOfMonth()->max($context->from)->addDays(9)->setTime(5, 30);
            $context->at($at, function () use ($org, $author, $approver, $lapsed, $women, $portal, $clinical, $n) {
                $plan = [
                    ['Follow-up reminder', 'service', 'sms', $lapsed, 'Dear {name}, it has been a while since your last visit. Book a check-up in the patient portal.'],
                    ['Free health check camp', 'marketing', 'inapp', $women, 'Dear {name}, join our free health check camp this Friday at the main hospital.'],
                ];
                if ($n % 2 === 0) {
                    $plan[] = ['Portal tips', 'service', 'inapp', $portal, 'Did you know you can see your reports and pay bills in the portal, {name}?'];
                }
                if ($clinical && $n === 1) {
                    $plan[] = ['Living well with your condition', 'marketing', 'inapp', $clinical, 'Dear {name}, our nurse educators are holding a short class on managing your condition.'];
                }
                foreach ($plan as [$name, $purpose, $channel, $segment, $body]) {
                    $c = $this->campaigns->create($org, $author, ['name' => $name.' '.now()->format('M'), 'purpose' => $purpose, 'channel' => $channel, 'segment_id' => $segment->id, 'body' => $body]);
                    $this->campaigns->approve($this->campaigns->submit($c), $approver);
                    $this->campaigns->run($c->fresh());
                    $this->summary['campaigns']++;
                }
            });
        }
    }

    /** A few visits booked online for the coming two weeks, using the same schedules and slot lock as the front desk. */
    private function bookAhead(HistoryContext $context, array $accounts): void
    {
        $schedule = DoctorSchedule::query()->where('organization_id', $context->organization->id)->where('is_active', true)->whereNotNull('provider_id')->orderBy('id')->get();
        if ($schedule->isEmpty() || $accounts === []) {
            return;
        }
        foreach ($schedule as $s) {
            $this->schedules->generateForRange($s, Carbon::now()->addDay()->startOfDay(), Carbon::now()->addDays(14));
        }
        $slots = AppointmentSlot::query()->where('organization_id', $context->organization->id)->where('status', 'available')->where('slot_datetime', '>', now()->addHours(3))->whereColumn('booked_count', '<', 'max_capacity')->orderBy('slot_datetime')->limit(40)->get()->shuffle();
        $booked = [];
        foreach (collect($accounts)->values()->shuffle()->take(10) as $n => $account) {
            $slot = $slots->shift();
            if (! $slot) {
                break;
            }
            try {
                $appointment = $this->appointments->book($account, $account->patient, Crypt::encryptString((string) $slot->id), 'Booked online (demo).');
                $booked[] = $appointment;
            } catch (PortalException|ValidationException) {
            }
        }
        if (count($booked) >= 3) { // one is moved to another day, one is cancelled
            $next = $slots->shift();
            $next && rescue(fn () => $this->appointments->reschedule($this->accountOf($booked[0]), $booked[0], Crypt::encryptString((string) $next->id)), null, false);
            rescue(fn () => $this->appointments->cancel($this->accountOf($booked[1]), $booked[1], 'Cannot come (demo).'), null, false);
        }
    }

    /** Three named logins for training: the sign-in details are printed at the end of the build. */
    private function showcase(HistoryContext $context, array $accounts): void
    {
        $picked = collect($accounts)->values()->take(3);
        $kinds = ['a patient who joined on their own', 'a patient who joined at the front desk', 'a patient with results, bills and messages'];
        foreach ($picked as $n => $account) {
            $this->logins[] = ['kind' => $kinds[$n], 'phone' => (string) $account->phone, 'patient' => trim($account->patient->first_name.' '.$account->patient->last_name), 'patient_no' => $account->patient->enterprise_patient_no];
        }
    }

    /** Built only after the run switched the payment provider to the sandbox (the gateway is chosen when the service is made). */
    private function payments(): PortalPaymentService
    {
        return app(PortalPaymentService::class);
    }

    private function accountOf(Appointment $appointment): PatientPortalAccount
    {
        return PatientPortalAccount::query()->where('patient_id', $appointment->patient_id)->firstOrFail();
    }

    /** @param list<int> $items @return list<int> */
    private function sample(array $items, int $n): array
    {
        if ($n <= 0 || $items === []) {
            return [];
        }
        $keys = (array) array_rand($items, min($n, count($items)));

        return array_map(fn ($k) => $items[$k], $keys);
    }
}
