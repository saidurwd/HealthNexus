<?php

namespace App\Services\Ai\UseCases;

use App\Models\Ai\AiOutput;
use App\Models\User;
use App\Services\Ai\AiCall;
use App\Services\Ai\AiGateway;
use App\Services\Ai\AiPolicy;
use App\Services\ModuleManager;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Use case: in what order finished studies could be read. The rule is plain and shown: priority first (stat, urgent, routine), then the study that has waited longest. It suggests a position and says why. The Radiology
 * worklist, its priorities and its assignments are not touched; nobody is assigned and nothing is reordered there.
 */
class RadiologyWorklistSuggest
{
    private const WEIGHT = ['stat' => 0, 'urgent' => 1, 'routine' => 2];

    public function __construct(private readonly AiGateway $gateway, private readonly AiPolicy $policy, private readonly ModuleManager $modules) {}

    public function suggest(User $user, ?int $organizationId = null): AiOutput
    {
        $org = $organizationId ?? (int) $user->organizations()->value('organizations.id');
        if (! $this->modules->isEnabled('radiology', $org)) {
            throw ValidationException::withMessages(['radiology' => __('The Radiology module is not switched on for this hospital.')]);
        }
        if (! $user->can('radiology.worklist.view') || ! $user->organizations()->where('organizations.id', $org)->exists()) {
            abort(403, __('You are not allowed to use AI on this worklist.'));
        }
        $rows = DB::table('radiology_examinations as e')->join('radiology_order_items as i', 'i.id', '=', 'e.order_item_id')->join('radiology_orders as o', 'o.id', '=', 'i.radiology_order_id')->leftJoin('radiology_procedures as p', 'p.id', '=', 'i.procedure_id')
            ->where('e.organization_id', $org)->whereIn('e.status', ['completed', 'images_available'])->whereNotIn('i.status', ['reported', 'cancelled'])->whereNull('o.deleted_at')
            ->get(['e.id', 'e.completed_at', 'e.assigned_radiologist_id', 'i.priority', 'o.accession_number', 'p.name as procedure_name', 'i.requested_procedure_name']);
        if ($rows->isEmpty()) {
            throw ValidationException::withMessages(['radiology' => __('No finished study is waiting to be reported.')]);
        }
        $now = CarbonImmutable::now();
        $ranked = $rows->sortBy([fn ($a, $b) => (self::WEIGHT[$a->priority] ?? 2) <=> (self::WEIGHT[$b->priority] ?? 2), fn ($a, $b) => strcmp((string) $a->completed_at, (string) $b->completed_at), fn ($a, $b) => $a->id <=> $b->id])->values();
        $shown = $ranked->take($this->policy->int('radiology.max_studies', 30));

        $items = [['ref' => 'wl:basis', 'text' => __('Ranked by priority (stat, urgent, routine) and then by how long the study has waited. The real worklist and priorities are not changed.'), 'section' => __('How this was ranked'), 'kind' => 'basis']];
        $items[] = ['ref' => 'wl:count', 'text' => __(':n finished studies are waiting to be reported; the first :m are listed.', ['n' => $ranked->count(), 'm' => $shown->count()]), 'section' => __('How this was ranked'), 'kind' => 'basis'];
        foreach ($shown as $k => $r) {
            $mins = $r->completed_at ? (int) max(0, CarbonImmutable::parse((string) $r->completed_at, 'UTC')->diffInMinutes($now)) : 0;
            $items[] = ['ref' => 'rx:'.$r->id, 'text' => __('Suggested position :p: accession :a, :x, priority :r, finished :h h :m min ago, :s.', ['p' => $k + 1, 'a' => $r->accession_number, 'x' => $r->procedure_name ?: $r->requested_procedure_name, 'r' => $r->priority, 'h' => intdiv($mins, 60), 'm' => $mins % 60,
                's' => $r->assigned_radiologist_id ? __('assigned to a radiologist') : __('not yet assigned')]), 'section' => __('Suggested reading order'), 'kind' => 'worklist', 'untrusted' => true];
        }

        return $this->gateway->run(new AiCall($org, $user, 'radiology_worklist', 'radiology_worklist', $items, 'worklist', __('Suggested reading order'), null, app()->getLocale() === 'bn' ? 'bn' : 'en',
            __('A suggestion from priority and waiting time only. It does not know the clinical question: the radiologist decides what to read first.')));
    }
}
