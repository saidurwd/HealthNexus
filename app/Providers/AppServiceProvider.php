<?php

namespace App\Providers;

use App\Contracts\Billing\RevenuePostingInterface;
use App\Contracts\Laboratory\LabAnalyzerAdapterInterface;
use App\Contracts\Pharmacy\DrugInformationProviderInterface;
use App\Events\Appointments\AppointmentCancelled;
use App\Events\Appointments\AppointmentCreated;
use App\Events\Appointments\AppointmentNoShow;
use App\Events\Appointments\AppointmentRescheduled;
use App\Events\Clinical\ClinicalOrderCreated;
use App\Events\Clinical\DiagnosisAdded;
use App\Events\Clinical\EncounterCompleted;
use App\Events\Clinical\EncounterCreated;
use App\Events\Clinical\PrescriptionIssued;
use App\Events\Clinical\ReferralCreated;
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
use App\Events\Pharmacy\DispensingCompleted;
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
use App\Events\Radiology\CriticalFindingDetected;
use App\Events\Radiology\RadiologyExamCompleted;
use App\Events\Radiology\RadiologyReportAmended;
use App\Events\Radiology\RadiologyReportFinalized;
use App\Listeners\Appointments\CancelAppointmentReminders;
use App\Listeners\Appointments\ScheduleAppointmentReminders;
use App\Listeners\Billing\CreateChargeOnClinicalOrderCreated;
use App\Listeners\Billing\CreateChargeOnEncounterCompleted;
use App\Listeners\Clinical\RecordEncounterTimelineEvent;
use App\Listeners\Ipd\NotifyOnAdmissionEvents;
use App\Listeners\Ipd\NotifyOnDischargeEvents;
use App\Listeners\Ipd\NotifyOnTransferEvents;
use App\Listeners\Ipd\RecordAdmissionOnPatientTimeline;
use App\Listeners\Nursing\CompleteEpisodeOnDischarge;
use App\Listeners\Nursing\FlagHandoverOnTransfer;
use App\Listeners\Nursing\NotifyOnAssignmentEvents;
use App\Listeners\Nursing\NotifyOnEscalationEvents;
use App\Listeners\Nursing\NotifyOnHandoverEvents;
use App\Listeners\Nursing\NotifyOnMarEvents;
use App\Listeners\Nursing\RaiseAlertOnCriticalObservation;
use App\Listeners\Nursing\RecordNursingEventsOnPatientTimeline;
use App\Listeners\Nursing\StartEpisodeOnAdmission;
use App\Listeners\Laboratory\CreateLabOrderOnClinicalOrderCreated;
use App\Listeners\Laboratory\NotifyOnCriticalResultDetected;
use App\Listeners\Laboratory\NotifyOnLabReportEvents;
use App\Listeners\Laboratory\NotifyOnSampleRejected;
use App\Listeners\Laboratory\RecordLabReportOnPatientTimeline;
use App\Listeners\Pharmacy\CreatePharmacyOrderOnPrescriptionIssued;
use App\Listeners\Pharmacy\RecordDispensingOnPatientTimeline;
use App\Listeners\Radiology\CreateRadiologyOrderOnClinicalOrderCreated;
use App\Listeners\Radiology\DispatchPacsSyncOnExamCompleted;
use App\Listeners\Radiology\NotifyOnCriticalFindingDetected;
use App\Listeners\Radiology\NotifyOnRadiologyReportEvents;
use App\Listeners\Radiology\RecordRadiologyReportOnPatientTimeline;
use App\Services\Billing\NullRevenuePoster;
use App\Services\Breadcrumbs;
use App\Services\Laboratory\NullLabAnalyzerAdapter;
use App\Services\Pharmacy\NullDrugInformationProvider;
use App\Services\SettingsService;
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
        Event::listen(LabReportAmended::class, [NotifyOnLabReportEvents::class, 'handleAmended']);

        Event::listen(ClinicalOrderCreated::class, CreateRadiologyOrderOnClinicalOrderCreated::class);
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
    }
}
