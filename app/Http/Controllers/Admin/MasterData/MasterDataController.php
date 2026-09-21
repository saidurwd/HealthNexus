<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Currency;
use App\Models\IdentificationType;
use App\Models\State;
use Illuminate\Http\Request;

class MasterDataController extends Controller
{
    public function index()
    {
        return view('admin.master-data.index');
    }

    public function countries(Request $request)
    {
        $countries = Country::query()
            ->when($request->filled('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20);

        return view('admin.master-data.countries', compact('countries'));
    }

    public function allStates(Request $request)
    {
        $states = State::query()
            ->with('country')
            ->when($request->filled('search'), function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('country', fn ($q2) => $q2->where('name', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(20);

        return view('admin.master-data.all-states', compact('states'));
    }

    public function states(Request $request, Country $country)
    {
        $states = State::query()
            ->where('country_id', $country->id)
            ->when($request->filled('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20);

        return view('admin.master-data.states', compact('country', 'states'));
    }

    public function currencies(Request $request)
    {
        $currencies = Currency::query()
            ->when($request->filled('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20);

        return view('admin.master-data.currencies', compact('currencies'));
    }

    public function identificationTypes(Request $request)
    {
        $types = IdentificationType::query()
            ->when($request->filled('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20);

        return view('admin.master-data.identification-types', compact('types'));
    }
}
