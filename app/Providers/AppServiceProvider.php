<?php

namespace App\Providers;

use App\Contracts\Billing\RevenuePostingInterface;
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
use App\Listeners\Appointments\CancelAppointmentReminders;
use App\Listeners\Appointments\ScheduleAppointmentReminders;
use App\Listeners\Billing\CreateChargeOnClinicalOrderCreated;
use App\Listeners\Billing\CreateChargeOnEncounterCompleted;
use App\Listeners\Clinical\RecordEncounterTimelineEvent;
use App\Services\Billing\NullRevenuePoster;
use App\Services\Breadcrumbs;
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
    }
}
