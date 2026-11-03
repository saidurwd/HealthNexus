<?php

namespace App\Http\Controllers\Lookup;

use App\Http\Controllers\Controller;
use App\Services\Geo\GeoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Finds divisions, districts, upazilas and unions for a searchable dropdown, in English or Bangla. Each level waits for the one above it (division_id, district_id, upazila_id). The places are the same for every hospital, so nothing is
 * scoped to one; the person must already hold a right on a screen that picks a place.
 */
class GeoLookupController extends Controller
{
    public function __construct(private readonly GeoService $geo) {}

    public function __invoke(Request $request, string $level): JsonResponse
    {
        abort_unless(Gate::any(array_merge((array) config('ux.lookup_permissions'), ['settings.view', 'patients.update', 'patients.create'])), 403);
        abort_unless(isset(GeoService::LEVELS[$level]), 404);
        $parent = isset(GeoService::PARENT[$level]) ? (int) $request->query(GeoService::PARENT[$level], 0) : null;
        if ($parent === 0) {
            return response()->json([]);
        }

        return response()->json(array_map(fn ($p) => ['value' => (string) $p->id, 'text' => $this->geo->label($p)], $this->geo->search($level, (string) $request->query('q', ''), $parent)));
    }
}
