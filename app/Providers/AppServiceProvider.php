<?php

namespace App\Providers;

use App\Contracts\Billing\ChargeEventPublisher;
use App\Contracts\Billing\RevenuePostingInterface;
use App\Contracts\Documents\PdfRenderer;
use App\Contracts\Integration\BloodRequestGateway;
use App\Contracts\Integration\NationalExchangeGateway;
use App\Contracts\Integration\PaymentGateway;
use App\Contracts\Laboratory\LabAnalyzerAdapterInterface;
use App\Contracts\Pharmacy\DrugInformationProviderInterface;
use App\Contracts\Stock\StockLedger;
use App\Events\Appointments\AppointmentCancelled;
use App\Events\Appointments\AppointmentCreated;
use App\Events\Appointments\AppointmentNoShow;
use App\Events\Appointments\AppointmentRescheduled;
use App\Events\Billing\ChargeReversalRequested;
use App\Events\Billing\InvoiceFinalized;
use App\Events\Clinical\ClinicalOrderCreated;
use App\Events\Clinical\DiagnosisAdded;
use App\Events\Clinical\EncounterCompleted;
use App\Events\Clinical\EncounterCreated;
use App\Events\Clinical\PrescriptionIssued;
use App\Events\Clinical\ReferralCreated;
use App\Events\Icu\ICUCodeBlueStarted;
use App\Events\Icu\ICUCriticalAlertTriggered;
use App\Events\Icu\ICUDischarged;
use App\Events\Icu\ICUPatientAdmitted;
use App\Events\Icu\ICUPatientTransferred;
use App\Events\Ipd\AdmissionApproved;
use App\Events\Ipd\BedAllocated;
use App\Events\Ipd\DischargeRequested;
use App\Events\Ipd\PatientAdmitted;
use App\Events\Ipd\PatientDischarged;
use App\Events\Ipd\PatientTransferred;
use App\Events\Laboratory\CriticalResultDetected;
use App\Events\Laboratory\LabReportAmended;
use App\Events\Laboratory\LabReportFinalized;
use App\Events\Laboratory\SampleRejected;
use App\Events\Nursing\CarePlanCompleted;
use App\Events\Nursing\CriticalObservationDetected;
use App\Events\Nursing\MedicationAdministrationRecorded;
use App\Events\Nursing\MedicationOmitted;
use App\Events\Nursing\MedicationRefused;
use App\Events\Nursing\NurseAssigned;
use App\Events\Nursing\NursingDischargeChecklistCompleted;
use App\Events\Nursing\NursingEpisodeStarted;
use App\Events\Nursing\NursingEscalationCreated;
use App\Events\Nursing\NursingEscalationResolved;
use App\Events\Nursing\NursingHandoverCreated;
use App\Events\Ot\PatientReceivedForSurgery;
use App\Events\Ot\RecoveryCompleted;
use App\Events\Ot\SurgeryCancelled;
use App\Events\Ot\SurgeryCompleted;
use App\Events\Ot\SurgeryPostponed;
use App\Events\Ot\SurgeryScheduled;
use App\Events\Ot\SurgeryStarted;
use App\Events\Ot\SurgicalComplicationRecorded;
use App\Events\Pharmacy\DispensingCompleted;
use App\Events\Radiology\CriticalFindingDetected;
use App\Events\Radiology\RadiologyExamCompleted;
use App\Events\Radiology\RadiologyReportAmended;
use App\Events\Radiology\RadiologyReportFinalized;
use App\Integrations\BloodBankRequestGateway;
use App\Integrations\Null\NullNationalExchangeGateway;
use App\Integrations\Null\NullPaymentGateway;
use App\Integrations\Sandbox\SandboxNationalExchangeGateway;
use App\Integrations\Sandbox\SandboxPaymentGateway;
use App\Listeners\Appointments\CancelAppointmentReminders;
use App\Listeners\Appointments\ScheduleAppointmentReminders;
use App\Listeners\Billing\CreateChargeOnClinicalOrderCreated;
use App\Listeners\Billing\CreateChargeOnEncounterCompleted;
use App\Listeners\Billing\ReverseChargeOnReversalRequested;
use App\Listeners\Blood\SyncDonationTestsOnLabReport;
use App\Listeners\Clinical\RecordEncounterTimelineEvent;
use App\Listeners\Icu\CreateIcuAlertOnLabCriticalResult;
use App\Listeners\Icu\NotifyOnIcuCriticalAlerts;
use App\Listeners\Icu\NotifyOnIcuEpisodeEvents;
use App\Listeners\Icu\RecordIcuEventsOnPatientTimeline;
use App\Listeners\Insurance\DraftClaimWhenInvoiceFinalized;
use App\Listeners\Interop\PublishDomainEvent;
use App\Listeners\Ipd\NotifyOnAdmissionEvents;
use App\Listeners\Ipd\NotifyOnDischargeEvents;
use App\Listeners\Ipd\NotifyOnTransferEvents;
use App\Listeners\Ipd\RecordAdmissionOnPatientTimeline;
use App\Listeners\Laboratory\CreateLabOrderOnClinicalOrderCreated;
use App\Listeners\Laboratory\NotifyOnCriticalResultDetected;
use App\Listeners\Laboratory\NotifyOnLabReportEvents;
use App\Listeners\Laboratory\NotifyOnSampleRejected;
use App\Listeners\Laboratory\RecordLabReportOnPatientTimeline;
use App\Listeners\Nursing\CompleteEpisodeOnDischarge;
use App\Listeners\Nursing\FlagHandoverOnTransfer;
use App\Listeners\Nursing\NotifyOnAssignmentEvents;
use App\Listeners\Nursing\NotifyOnEscalationEvents;
use App\Listeners\Nursing\NotifyOnHandoverEvents;
use App\Listeners\Nursing\NotifyOnMarEvents;
use App\Listeners\Nursing\RaiseAlertOnCriticalObservation;
use App\Listeners\Nursing\RecordNursingEventsOnPatientTimeline;
use App\Listeners\Nursing\StartEpisodeOnAdmission;
use App\Listeners\Ot\CreateSurgeryRequestOnClinicalOrderCreated;
use App\Listeners\Ot\NotifyOnSurgeryScheduleEvents;
use App\Listeners\Ot\NotifyOnSurgeryTeamEvents;
use App\Listeners\Ot\RecordSurgeryEventsOnPatientTimeline;
use App\Listeners\Pharmacy\CreatePharmacyOrderOnPrescriptionIssued;
use App\Listeners\Pharmacy\RecordDispensingOnPatientTimeline;
use App\Listeners\Radiology\CreateRadiologyOrderOnClinicalOrderCreated;
use App\Listeners\Radiology\DispatchPacsSyncOnExamCompleted;
use App\Listeners\Radiology\NotifyOnCriticalFindingDetected;
use App\Listeners\Radiology\NotifyOnRadiologyReportEvents;
use App\Listeners\Radiology\RecordRadiologyReportOnPatientTimeline;
use App\Models\Patient;
use App\Models\PatientBranchRegistration;
use App\Models\PatientContact;
use App\Models\PatientIdentifier;
use App\Observers\PatientSearchObserver;
use App\Services\Billing\Events\BillingChargeEventPublisher;
use App\Services\Billing\NullRevenuePoster;
use App\Services\Breadcrumbs;
use App\Services\Documents\BrowsershotPdfRenderer;
use App\Services\Interop\DeliveryWorker;
use App\Services\Laboratory\NullLabAnalyzerAdapter;
use App\Services\ModuleManager;
use App\Services\Ops\ShellRunner;
use App\Services\Ops\SymfonyShellRunner;
use App\Services\Pharmacy\NullDrugInformationProvider;
use App\Services\Search\PatientSearchIndexer;
use App\Services\SettingsService;
use App\Services\Stock\DatabaseStockLedger;
use App\Services\TenantContextResolver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContextResolver::class, function ($app) {
            return new TenantContextResolver;
        });

        $this->app->singleton(ModuleManager::class);
        $this->app->bind(ShellRunner::class, SymfonyShellRunner::class);
        $this->app->bind(PdfRenderer::class, BrowsershotPdfRenderer::class);
        $this->app->bind(StockLedger::class, DatabaseStockLedger::class);
        $this->app->bind(ChargeEventPublisher::class, BillingChargeEventPublisher::class);

        // Phase 13: the real Blood Bank answers the gateway (it replies "not enabled" itself when the module is off for the organization).
        $this->app->bind(BloodRequestGateway::class, BloodBankRequestGateway::class);

        // Phase 17: the payment gateway is the sandbox only where the config says so (development, tests, the demo); otherwise the null adapter answers.
        $this->app->bind(PaymentGateway::class, fn () => config('portal.payments.driver') === 'sandbox' ? new SandboxPaymentGateway : new NullPaymentGateway);

        $this->app->singleton(DeliveryWorker::class); // one sender per process, so a transport set on it (tests, a hospital adapter) is the one used

        // Phase 18: the national exchange answers with the sandbox test client only where the config says so; otherwise the null adapter does.
        $this->app->bind(NationalExchangeGateway::class, fn () => config('interop.national.driver') === 'sandbox' ? new SandboxNationalExchangeGateway : new NullNationalExchangeGateway);

        foreach (config('integrations.gateways', []) as $contract => $nullAdapter) {
            $this->app->bindIf($contract, $nullAdapter);
        }

        $this->app->singleton(Breadcrumbs::class, function ($app) {
            return new Breadcrumbs($app['request']);
        });

        $this->app->singleton(SettingsService::class, function () {
            return new SettingsService;
        });

        $this->app->bind(RevenuePostingInterface::class, NullRevenuePoster::class);
        $this->app->bind(LabAnalyzerAdapterInterface::class, NullLabAnalyzerAdapter::class);
        $this->app->bind(DrugInformationProviderInterface::class, NullDrugInformationProvider::class);
    }

    public function boot(): void
    {
        Patient::observe(PatientSearchObserver::class);
        foreach ([PatientIdentifier::class, PatientContact::class, PatientBranchRegistration::class] as $child) {
            $reindex = function ($row) {
                $patient = Patient::withoutGlobalScopes()->find($row->patient_id);
                $patient && app(PatientSearchIndexer::class)->reindex($patient);
            };
            $child::saved($reindex);
            $child::deleted($reindex);
        }

        Event::listen(ChargeReversalRequested::class, ReverseChargeOnReversalRequested::class);
        Event::listen(EncounterCompleted::class, CreateChargeOnEncounterCompleted::class);
        Event::listen(ClinicalOrderCreated::class, CreateChargeOnClinicalOrderCreated::class);

        Event::listen(EncounterCreated::class, [RecordEncounterTimelineEvent::class, 'handleEncounterCreated']);
        Event::listen(EncounterCompleted::class, [RecordEncounterTimelineEvent::class, 'handleEncounterCompleted']);
        Event::listen(DiagnosisAdded::class, [RecordEncounterTimelineEvent::class, 'handleDiagnosisAdded']);
        Event::listen(PrescriptionIssued::class, [RecordEncounterTimelineEvent::class, 'handlePrescriptionIssued']);
        Event::listen(PrescriptionIssued::class, CreatePharmacyOrderOnPrescriptionIssued::class);
        Event::listen(ReferralCreated::class, [RecordEncounterTimelineEvent::class, 'handleReferralCreated']);

        Event::listen(AppointmentCreated::class, [ScheduleAppointmentReminders::class, 'handleCreated']);
        Event::listen(AppointmentRescheduled::class, [ScheduleAppointmentReminders::class, 'handleRescheduled']);
        Event::listen(AppointmentCancelled::class, [CancelAppointmentReminders::class, 'handleCancelled']);
        Event::listen(AppointmentNoShow::class, [CancelAppointmentReminders::class, 'handleNoShow']);

        Event::listen(ClinicalOrderCreated::class, CreateLabOrderOnClinicalOrderCreated::class);
        Event::listen(SampleRejected::class, NotifyOnSampleRejected::class);
        Event::listen(CriticalResultDetected::class, NotifyOnCriticalResultDetected::class);
        Event::listen(LabReportFinalized::class, [RecordLabReportOnPatientTimeline::class, 'handleFinalized']);
        Event::listen(LabReportAmended::class, [RecordLabReportOnPatientTimeline::class, 'handleAmended']);
        Event::listen(LabReportFinalized::class, [NotifyOnLabReportEvents::class, 'handleFinalized']);
        Event::listen(LabReportFinalized::class, [SyncDonationTestsOnLabReport::class, 'handle']);
        Event::listen(InvoiceFinalized::class, [DraftClaimWhenInvoiceFinalized::class, 'handle']);
        Event::listen(LabReportAmended::class, [NotifyOnLabReportEvents::class, 'handleAmended']);

        Event::listen(ClinicalOrderCreated::class, CreateRadiologyOrderOnClinicalOrderCreated::class);
        Event::listen(ClinicalOrderCreated::class, CreateSurgeryRequestOnClinicalOrderCreated::class);
        Event::listen(RadiologyExamCompleted::class, DispatchPacsSyncOnExamCompleted::class);
        Event::listen(CriticalFindingDetected::class, NotifyOnCriticalFindingDetected::class);
        Event::listen(RadiologyReportFinalized::class, [RecordRadiologyReportOnPatientTimeline::class, 'handleFinalized']);
        Event::listen(RadiologyReportAmended::class, [RecordRadiologyReportOnPatientTimeline::class, 'handleAmended']);
        Event::listen(RadiologyReportFinalized::class, [NotifyOnRadiologyReportEvents::class, 'handleFinalized']);
        Event::listen(RadiologyReportAmended::class, [NotifyOnRadiologyReportEvents::class, 'handleAmended']);

        Event::listen(DispensingCompleted::class, RecordDispensingOnPatientTimeline::class);

        Event::listen(PatientAdmitted::class, [RecordAdmissionOnPatientTimeline::class, 'handleAdmitted']);
        Event::listen(PatientTransferred::class, [RecordAdmissionOnPatientTimeline::class, 'handleTransferred']);
        Event::listen(PatientDischarged::class, [RecordAdmissionOnPatientTimeline::class, 'handleDischarged']);
        Event::listen(AdmissionApproved::class, [NotifyOnAdmissionEvents::class, 'handleApproved']);
        Event::listen(BedAllocated::class, [NotifyOnAdmissionEvents::class, 'handleBedAllocated']);
        Event::listen(PatientTransferred::class, [NotifyOnTransferEvents::class, 'handleCompleted']);
        Event::listen(DischargeRequested::class, [NotifyOnDischargeEvents::class, 'handleRequested']);
        Event::listen(PatientDischarged::class, [NotifyOnDischargeEvents::class, 'handleCompleted']);

        Event::listen(PatientAdmitted::class, [StartEpisodeOnAdmission::class, 'handle']);
        Event::listen(PatientTransferred::class, [FlagHandoverOnTransfer::class, 'handle']);
        Event::listen(PatientDischarged::class, [CompleteEpisodeOnDischarge::class, 'handle']);

        Event::listen(NursingEpisodeStarted::class, [RecordNursingEventsOnPatientTimeline::class, 'handleEpisodeStarted']);
        Event::listen(MedicationAdministrationRecorded::class, [RecordNursingEventsOnPatientTimeline::class, 'handleMedicationAdministered']);
        Event::listen(CarePlanCompleted::class, [RecordNursingEventsOnPatientTimeline::class, 'handleCarePlanCompleted']);
        Event::listen(NursingEscalationCreated::class, [RecordNursingEventsOnPatientTimeline::class, 'handleEscalationCreated']);
        Event::listen(NursingDischargeChecklistCompleted::class, [RecordNursingEventsOnPatientTimeline::class, 'handleDischargeChecklistCompleted']);

        Event::listen(CriticalObservationDetected::class, [RaiseAlertOnCriticalObservation::class, 'handle']);
        Event::listen(MedicationRefused::class, [NotifyOnMarEvents::class, 'handleRefused']);
        Event::listen(MedicationOmitted::class, [NotifyOnMarEvents::class, 'handleOmitted']);
        Event::listen(NursingEscalationCreated::class, [NotifyOnEscalationEvents::class, 'handleCreated']);
        Event::listen(NursingEscalationResolved::class, [NotifyOnEscalationEvents::class, 'handleResolved']);
        Event::listen(NurseAssigned::class, [NotifyOnAssignmentEvents::class, 'handle']);
        Event::listen(NursingHandoverCreated::class, [NotifyOnHandoverEvents::class, 'handleCreated']);

        Event::listen(SurgeryScheduled::class, [RecordSurgeryEventsOnPatientTimeline::class, 'handleScheduled']);
        Event::listen(SurgeryStarted::class, [RecordSurgeryEventsOnPatientTimeline::class, 'handleStarted']);
        Event::listen(SurgeryCompleted::class, [RecordSurgeryEventsOnPatientTimeline::class, 'handleCompleted']);
        Event::listen(RecoveryCompleted::class, [RecordSurgeryEventsOnPatientTimeline::class, 'handleRecoveryCompleted']);
        Event::listen(SurgeryScheduled::class, [NotifyOnSurgeryScheduleEvents::class, 'handleScheduled']);
        Event::listen(SurgeryPostponed::class, [NotifyOnSurgeryScheduleEvents::class, 'handlePostponed']);
        Event::listen(SurgeryCancelled::class, [NotifyOnSurgeryScheduleEvents::class, 'handleCancelled']);
        Event::listen(PatientReceivedForSurgery::class, [NotifyOnSurgeryTeamEvents::class, 'handlePatientReceived']);
        Event::listen(SurgicalComplicationRecorded::class, [NotifyOnSurgeryTeamEvents::class, 'handleComplication']);

        Event::listen(CriticalResultDetected::class, CreateIcuAlertOnLabCriticalResult::class);
        Event::listen(ICUPatientAdmitted::class, [RecordIcuEventsOnPatientTimeline::class, 'handleAdmitted']);
        Event::listen(ICUPatientTransferred::class, [RecordIcuEventsOnPatientTimeline::class, 'handleTransferred']);
        Event::listen(ICUDischarged::class, [RecordIcuEventsOnPatientTimeline::class, 'handleDischarged']);
        Event::listen(ICUPatientAdmitted::class, [NotifyOnIcuEpisodeEvents::class, 'handleAdmitted']);
        Event::listen(ICUPatientTransferred::class, [NotifyOnIcuEpisodeEvents::class, 'handleTransferred']);
        Event::listen(ICUCriticalAlertTriggered::class, [NotifyOnIcuCriticalAlerts::class, 'handleAlertTriggered']);
        Event::listen(ICUCodeBlueStarted::class, [NotifyOnIcuCriticalAlerts::class, 'handleCodeBlueStarted']);

        // Phase 18: the domain events offered to integration routing rules (config/interop.php).
        foreach (array_values((array) config('interop.events', [])) as $eventClass) {
            Event::listen($eventClass, [PublishDomainEvent::class, 'handle']);
        }
    }
}
