<?php

namespace App\Providers;

use App\Contracts\Billing\RevenuePostingInterface;
use App\Contracts\Laboratory\LabAnalyzerAdapterInterface;
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
use App\Events\Laboratory\CriticalResultDetected;
use App\Events\Laboratory\LabReportAmended;
use App\Events\Laboratory\LabReportFinalized;
use App\Events\Laboratory\SampleRejected;
use App\Events\Radiology\CriticalFindingDetected;
use App\Events\Radiology\RadiologyExamCompleted;
use App\Events\Radiology\RadiologyReportAmended;
use App\Events\Radiology\RadiologyReportFinalized;
use App\Listeners\Appointments\CancelAppointmentReminders;
use App\Listeners\Appointments\ScheduleAppointmentReminders;
use App\Listeners\Billing\CreateChargeOnClinicalOrderCreated;
use App\Listeners\Billing\CreateChargeOnEncounterCompleted;
use App\Listeners\Clinical\RecordEncounterTimelineEvent;
use App\Listeners\Laboratory\CreateLabOrderOnClinicalOrderCreated;
use App\Listeners\Laboratory\NotifyOnCriticalResultDetected;
use App\Listeners\Laboratory\NotifyOnLabReportEvents;
use App\Listeners\Laboratory\NotifyOnSampleRejected;
use App\Listeners\Laboratory\RecordLabReportOnPatientTimeline;
use App\Listeners\Radiology\CreateRadiologyOrderOnClinicalOrderCreated;
use App\Listeners\Radiology\DispatchPacsSyncOnExamCompleted;
use App\Listeners\Radiology\NotifyOnCriticalFindingDetected;
use App\Listeners\Radiology\NotifyOnRadiologyReportEvents;
use App\Listeners\Radiology\RecordRadiologyReportOnPatientTimeline;
use App\Services\Billing\NullRevenuePoster;
use App\Services\Breadcrumbs;
use App\Services\Laboratory\NullLabAnalyzerAdapter;
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
    }

    public function boot(): void
    {
        Event::listen(EncounterCompleted::class, CreateChargeOnEncounterCompleted::class);
        Event::listen(ClinicalOrderCreated::class, CreateChargeOnClinicalOrderCreated::class);

        Event::listen(EncounterCreated::class, [RecordEncounterTimelineEvent::class, 'handleEncounterCreated']);
        Event::listen(EncounterCompleted::class, [RecordEncounterTimelineEvent::class, 'handleEncounterCompleted']);
        Event::listen(DiagnosisAdded::class, [RecordEncounterTimelineEvent::class, 'handleDiagnosisAdded']);
        Event::listen(PrescriptionIssued::class, [RecordEncounterTimelineEvent::class, 'handlePrescriptionIssued']);
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
    }
}
