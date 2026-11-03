<?php

namespace App\Listeners\Interop;

use App\Models\Encounter;
use App\Services\Interop\EventPublisher;
use App\Services\ModuleManager;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/** One listener for every domain event offered to routing rules (config/interop.php `events`). Never lets an integration problem reach the work that raised the event. */
class PublishDomainEvent
{
    public function __construct(private readonly EventPublisher $publisher, private readonly ModuleManager $modules) {}

    public function handle(object $event): void
    {
        try {
            [$key, $subject, $attributes] = $this->describe($event);
            if ($key === null || $subject === null) {
                return;
            }
            $org = (int) ($subject->getAttribute('organization_id') ?? 0);
            if ($org > 0 && $this->modules->isEnabled('interop', $org)) {
                $this->publisher->publish($key, $subject, $attributes);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** @return array{0:?string,1:?Model,2:array<string,mixed>} */
    private function describe(object $event): array
    {
        $map = array_flip((array) config('interop.events'));
        $key = $map[$event::class] ?? null;
        $subject = match (true) {
            property_exists($event, 'patient') => $event->patient,
            property_exists($event, 'encounter') && ! property_exists($event, 'prescription') => $event->encounter,
            property_exists($event, 'appointment') => $event->appointment,
            property_exists($event, 'invoice') => $event->invoice,
            property_exists($event, 'prescription') => $event->prescription,
            property_exists($event, 'report') => $event->report,
            default => null,
        };
        $attributes = $subject instanceof Encounter ? ['encounter_type' => $subject->encounter_type, 'branch_id' => $subject->branch_id] : [];

        return [$key, $subject instanceof Model ? $subject : null, $attributes];
    }
}
