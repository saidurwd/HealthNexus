<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Services\ModuleManager;
use App\Support\Dashboard\Days;
use Illuminate\Support\Facades\DB;

/**
 * "What is waiting for me": the counts a person sees first on the home page, only for the work their rights let them do and the modules their hospital has on. Each one is a plain count of a list the tile opens. The counts are the
 * person's hospital only; ones that depend on the person (their own actions, their unread notices) are worked out for that person.
 */
class HomeTiles
{
    public function __construct(private readonly ModuleManager $modules) {}

    /** @return list<array{key:string,label:string,icon:string,variant:string,count:int,route:string,hint:?string}> most waiting first */
    public function forUser(User $user, int $organizationId): array
    {
        $out = [];
        foreach ($this->definitions($user, $organizationId) as $d) {
            if (($d['permission'] && ! $user->can($d['permission'])) || ($d['module'] && ! $this->modules->isEnabled($d['module'], $organizationId))) {
                continue;
            }
            $out[] = ['key' => $d['key'], 'label' => $d['label'], 'icon' => $d['icon'], 'variant' => $d['variant'], 'count' => (int) ($d['count'])(), 'route' => $d['route'], 'hint' => $d['hint'] ?? null];
        }
        usort($out, fn ($a, $b) => $b['count'] <=> $a['count']);

        return $out;
    }

    /** @return list<array<string,mixed>> */
    private function definitions(User $user, int $org): array
    {
        $now = now();
        [$dayStart, $dayEnd] = Days::bounds(Days::today());
        $t = fn (string $table) => DB::table($table)->where("$table.organization_id", $org);

        return [
            ['key' => 'queue', 'label' => __('Patients waiting in the queue'), 'icon' => 'bi-people', 'variant' => 'primary', 'permission' => 'appointments.queue', 'module' => 'appointment', 'route' => 'admin.queue.index',
                'count' => fn () => $t('appointment_tokens')->join('appointments', 'appointments.id', '=', 'appointment_tokens.appointment_id')->where('appointment_tokens.status', 'waiting')->whereDate('appointments.appointment_date', Days::today()->toDateString())->count()],
            ['key' => 'lab', 'label' => __('Lab orders to work'), 'icon' => 'bi-clipboard2-pulse', 'variant' => 'info', 'permission' => 'lab.dashboard.view', 'module' => 'lab', 'route' => 'admin.lab.worklist.index',
                'count' => fn () => $t('lab_orders')->whereNull('deleted_at')->whereIn('status', ['registered', 'awaiting_collection', 'collected', 'received', 'awaiting_validation'])->count()],
            ['key' => 'radiology', 'label' => __('Studies waiting to be reported'), 'icon' => 'bi-radioactive', 'variant' => 'info', 'permission' => 'radiology.worklist.view', 'module' => 'radiology', 'route' => 'admin.radiology.worklist.index',
                'count' => fn () => $t('radiology_examinations')->join('radiology_order_items as i', 'i.id', '=', 'radiology_examinations.order_item_id')->whereIn('radiology_examinations.status', ['completed', 'images_available'])->whereNotIn('i.status', ['reported', 'cancelled'])->count()],
            ['key' => 'pharmacy', 'label' => __('Prescriptions to dispense'), 'icon' => 'bi-capsule', 'variant' => 'success', 'permission' => 'pharmacy.dashboard.view', 'module' => 'pharmacy', 'route' => 'admin.pharmacy.orders.index',
                'count' => fn () => $t('pharmacy_orders')->whereIn('status', ['pending', 'partially_dispensed'])->count()],
            ['key' => 'nursing', 'label' => __('Nursing tasks overdue'), 'icon' => 'bi-alarm', 'variant' => 'danger', 'permission' => 'nursing.dashboard.view', 'module' => 'nursing', 'route' => 'admin.nursing.tasks.index',
                'count' => fn () => $t('nursing_tasks')->where('status', 'pending')->where('due_at', '<', $now)->count()],
            ['key' => 'adjustments', 'label' => __('Discounts waiting for your approval'), 'icon' => 'bi-percent', 'variant' => 'warning', 'permission' => 'billing.adjustment.approve', 'module' => null, 'route' => 'admin.billing.adjustments.index',
                'count' => fn () => $t('billing_adjustments')->where('status', 'requested')->where('requested_by', '!=', $user->id)->count(), 'hint' => __('Discounts you asked for yourself are not counted.')],
            ['key' => 'welfare_assess', 'label' => __('Welfare assessments to decide'), 'icon' => 'bi-clipboard2-check', 'variant' => 'warning', 'permission' => 'welfare.assessment.decide', 'module' => 'welfare', 'route' => 'admin.welfare.assessments',
                'count' => fn () => $t('wf_assessments')->where('status', 'submitted')->where('assessed_by', '!=', $user->id)->count()],
            ['key' => 'welfare_grants', 'label' => __('Welfare grants to approve'), 'icon' => 'bi-gift', 'variant' => 'warning', 'permission' => 'welfare.grant.approve', 'module' => 'welfare', 'route' => 'admin.welfare.grants',
                'count' => fn () => $t('wf_grants')->where('status', 'requested')->where('requested_by', '!=', $user->id)->count()],
            ['key' => 'quality', 'label' => __('Your quality actions past their date'), 'icon' => 'bi-shield-exclamation', 'variant' => 'danger', 'permission' => 'quality.action.view', 'module' => 'quality', 'route' => 'admin.quality.actions',
                'count' => fn () => $t('qa_actions')->where('owner_id', $user->id)->where('status', 'open')->where('due_on', '<', Days::today()->toDateString())->count()],
            ['key' => 'notices', 'label' => __('Unread notices'), 'icon' => 'bi-bell', 'variant' => 'secondary', 'permission' => null, 'module' => null, 'route' => 'admin.notifications.index',
                'count' => fn () => DB::table('notifications')->where('user_id', $user->id)->where('is_read', false)->count()],
        ];
    }
}
