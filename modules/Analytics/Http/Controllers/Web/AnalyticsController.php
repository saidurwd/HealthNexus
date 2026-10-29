<?php

namespace Modules\Analytics\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Analytics\Semantic\ScopeContext;
use App\Services\TenantContextResolver;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/** Shared guards for the analytics screens: another hospital's record does not exist (404); a missing right is 403; the scope is always the signed-in user's. */
abstract class AnalyticsController extends Controller
{
    public function __construct(protected readonly TenantContextResolver $tenant) {}

    protected function allow(Request $request, string $permission): void
    {
        abort_unless($request->user()->can($permission), 403);
    }

    protected function organizationId(): int
    {
        return (int) $this->tenant->getOrganizationId();
    }

    protected function scope(Request $request): ScopeContext
    {
        return ScopeContext::for($request->user(), $this->organizationId());
    }

    /**
     * @template T of Model
     *
     * @param  class-string<T>  $class
     * @return T
     */
    protected function find(string $class, string $publicId, bool $orSystem = false): Model
    {
        return $class::query()->where('public_id', $publicId)->where(fn ($q) => $q->where('organization_id', $this->organizationId())->when($orSystem, fn ($w) => $w->orWhereNull('organization_id')))->firstOrFail();
    }

    /** @return array{0:string,1:string} */
    protected function range(Request $request): array
    {
        $r = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);
        $to = $r['to'] ?? CarbonImmutable::now((string) config('analytics.timezone', 'Asia/Dhaka'))->toDateString();
        $from = $r['from'] ?? CarbonImmutable::parse($to)->subDays((int) config('analytics.dashboard.default_days', 30) - 1)->toDateString();

        return [$from, max($from, $to)];
    }
}
