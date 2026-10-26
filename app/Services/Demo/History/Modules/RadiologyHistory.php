<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Provider;
use App\Models\Radiology\RadiologyExamination;
use App\Models\Radiology\RadiologyModality;
use App\Models\Radiology\RadiologyOrder;
use App\Models\Radiology\RadiologyOrderItem;
use App\Models\User;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use App\Services\Radiology\RadiologyCriticalFindingService;
use App\Services\Radiology\RadiologyExaminationService;
use App\Services\Radiology\RadiologyReportService;
use App\Services\Radiology\RadiologySchedulingService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/** Schedules, performs and reports the imaging orders OPD created; a few get a critical finding that is acknowledged. */
class RadiologyHistory implements DemoHistoryModule
{
    private const FINDINGS = [['No acute abnormality seen.', 'Normal study.'], ['Mild degenerative changes.', 'Degenerative changes, no acute finding.'], ['Prominent bronchovascular markings.', 'Features of bronchitis.'], ['Fatty liver, grade 1.', 'Grade 1 fatty liver.']];

    public function __construct(
        private readonly RadiologySchedulingService $scheduling,
        private readonly RadiologyExaminationService $examinations,
        private readonly RadiologyReportService $reports,
        private readonly RadiologyCriticalFindingService $critical,
    ) {}

    public function name(): string
    {
        return 'Radiology';
    }

    public function module(): string
    {
        return 'radiology';
    }

    public function run(HistoryContext $context): string
    {
        $technologist = $this->provider($context, $context->staff('radiology_technician')->first(), 'radiology_technician');
        $radiologist = $this->provider($context, $context->staff('radiologist')->first(), 'radiologist');
        $approver = $this->provider($context, $context->staff('radiologist')->first() === $context->actor ? $context->actor : User::query()->role('senior_radiologist')->first() ?? $this->seniorUser($context), 'senior_radiologist');
        $recent = $context->to->subDay()->startOfDay();
        $counts = ['orders' => 0, 'reported' => 0, 'in_progress' => 0, 'critical' => 0];

        RadiologyOrder::query()->where('organization_id', $context->organization->id)->with('items.procedure')->orderBy('id')->each(function (RadiologyOrder $order) use ($context, $technologist, $radiologist, $approver, $recent, &$counts) {
            $counts['orders']++;
            $start = $order->ordered_at->toImmutable();
            $stage = $start->gte($recent) ? mt_rand(0, 2) : 9;

            foreach ($order->items as $item) {
                $modality = $this->modality($context, $item->procedure?->modality_type ?? 'XR');
                $exam = $this->schedule($context, $item, $modality, $technologist, $start->addHour());
                if ($stage === 0) {
                    $counts['in_progress']++;

                    continue;
                }
                $at = $exam->scheduled_at->toImmutable();
                $exam = $context->at($at, fn () => $this->examinations->checkIn($exam, $technologist->user));
                $exam = $context->at($at->addMinutes(5), fn () => $this->examinations->start($exam, $technologist->user));
                $exam = $context->at($at->addMinutes(25), fn () => $this->examinations->complete($exam, $technologist->user, ['technical_notes' => 'Routine acquisition.']));
                if ($stage === 1) {
                    $counts['in_progress']++;

                    continue;
                }
                [$findings, $impression] = $context->pick(self::FINDINGS);
                $report = $context->at($at->addHours(2), fn () => $this->reports->createDraft($exam, $radiologist, ['findings' => $findings, 'impression' => $impression]));
                $report = $context->at($at->addHours(2)->addMinutes(20), fn () => $this->reports->submit($report));
                if ($stage === 2) {
                    $counts['in_progress']++;

                    continue;
                }
                if ($context->chance(4)) {
                    $context->at($at->addHours(2)->addMinutes(25), fn () => $this->critical->flag($report, 'Urgent finding needing same-day clinical review', $radiologist));
                    $counts['critical']++;
                }
                $context->at($at->addHours(3), fn () => $this->reports->approve($report, $approver));
                $counts['reported']++;
            }
        });

        return sprintf('%d orders: %d reported, %d in progress, %d critical findings', $counts['orders'], $counts['reported'], $counts['in_progress'], $counts['critical']);
    }

    /** Books the next free half-hour on the modality; the scheduler refuses overlaps, so step forward until one is accepted. */
    private function schedule(HistoryContext $context, RadiologyOrderItem $item, RadiologyModality $modality, Provider $technologist, CarbonImmutable $from): RadiologyExamination
    {
        $time = $from->setTime(max(8, min(18, $from->hour)), $from->minute < 30 ? 0 : 30);
        for ($attempt = 0; $attempt < 40; $attempt++) {
            try {
                return $context->at($time->subMinutes(30), fn () => $this->scheduling->schedule($item->refresh(), $modality, $technologist, Carbon::instance($time), $context->actor));
            } catch (ValidationException) {
                $time = $time->addMinutes(30);
                if ($time->hour >= 20) {
                    $time = $time->addDay()->setTime(8, 0);
                }
            }
        }
        throw new \RuntimeException('No free imaging slot found.');
    }

    private function modality(HistoryContext $context, string $type): RadiologyModality
    {
        return RadiologyModality::query()->firstOrCreate(
            ['organization_id' => $context->organization->id, 'branch_id' => $context->branch->id, 'modality_type' => $type],
            ['code' => $type.'-01', 'name' => $type.' unit 1', 'status' => 'online', 'is_active' => true],
        );
    }

    private function provider(HistoryContext $context, User $user, string $type): Provider
    {
        return Provider::query()->firstOrCreate(
            ['organization_id' => $context->organization->id, 'user_id' => $user->id, 'provider_type' => $type],
            ['branch_id' => $context->branch->id, 'provider_code' => strtoupper(substr($type, 0, 4)).'-'.$user->id, 'name' => $user->name, 'status' => 'active'],
        )->setRelation('user', $user);
    }

    private function seniorUser(HistoryContext $context): User
    {
        $user = User::create(['name' => 'Senior Radiologist 1', 'email' => sprintf('demo.senior-radiologist.%s@%s.example.invalid', $context->organization->id, strtolower($context->organization->code)), 'password' => bcrypt(str()->random(24)), 'is_active' => true]);
        $user->organizations()->attach($context->organization->id, ['access_level' => 'staff']);
        $user->branches()->attach($context->branch->id, ['access_level' => 'staff', 'organization_id' => $context->organization->id]);
        $user->assignRole('senior_radiologist');

        return $user;
    }
}
