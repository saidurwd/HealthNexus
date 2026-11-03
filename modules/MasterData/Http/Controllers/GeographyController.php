<?php

namespace Modules\MasterData\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Geo\GeoUnion;
use App\Services\AuditLogger;
use App\Services\Dashboard\DashboardCache;
use App\Services\Geo\GeoOverview;
use App\Services\Geo\GeoService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** The country's places (division, district, upazila, union) and where the hospital's patients come from. Seeing is for anyone who may see master data; adding unions needs the right to change settings. */
class GeographyController extends Controller
{
    public function __construct(private readonly GeoService $geo, private readonly GeoOverview $overview, private readonly DashboardCache $cache, private readonly TenantContextResolver $tenant) {}

    public function index(Request $request)
    {
        $org = (int) $this->tenant->getOrganizationId();
        $branch = $this->tenant->getBranchId() ? (int) $this->tenant->getBranchId() : null;
        $r = $this->cache->remember($request->user(), 'geography', $org, $branch, null, fn () => $this->overview->summary($org), fresh: $request->boolean('fresh'));
        $unions = GeoUnion::query()->with('upazila.district')->when(trim((string) $request->query('q', '')) !== '', function ($q) use ($request) {
            $like = GeoService::like(trim((string) $request->query('q')));
            $q->where(fn ($w) => $w->whereRaw("name_en like ? escape '!'", [$like])->orWhereRaw("name_bn like ? escape '!'", [$like]));
        })->orderBy('name_en')->paginate(20)->withQueryString();

        return view('admin.master-data.geography', ['s' => $r['data'], 'asOf' => $r['as_of'], 'unions' => $unions, 'canManage' => $request->user()->can('settings.update')]);
    }

    public function storeUnion(Request $request, AuditLogger $audit)
    {
        abort_unless($request->user()->can('settings.update'), 403);
        $v = $request->validate(['upazila_id' => ['required', 'integer', 'exists:geo_upazilas,id'], 'name_en' => ['required', 'string', 'max:80'], 'name_bn' => ['nullable', 'string', 'max:80'], 'code' => ['nullable', 'string', 'max:12']]);
        $union = $this->geo->addUnion((int) $v['upazila_id'], $v['name_en'], $v['name_bn'] ?? null, $v['code'] ?? null);
        $audit->log('CREATE', GeoUnion::class, $union->id, null, $union->toArray(), $request);

        return back()->with('success', __('Union saved.'));
    }

    public function importUnions(Request $request, AuditLogger $audit)
    {
        abort_unless($request->user()->can('settings.update'), 403);
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);
        $r = $this->geo->importUnions($request->file('file')->getRealPath());
        $audit->log('IMPORT', GeoUnion::class, null, null, ['added' => $r['added'], 'updated' => $r['updated'], 'skipped' => count($r['skipped'])], $request);

        return back()->with('success', __(':a unions added, :u updated, :s skipped.', ['a' => $r['added'], 'u' => $r['updated'], 's' => count($r['skipped'])]))->with('import_skipped', array_slice($r['skipped'], 0, 20));
    }
}
