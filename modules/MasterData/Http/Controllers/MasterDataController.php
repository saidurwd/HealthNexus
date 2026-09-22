<?php

namespace Modules\MasterData\Http\Controllers;

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

    // Countries
    public function countries(Request $request)
    {
        $countries = Country::query()
            ->when($request->filled('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20);

        return view('admin.master-data.countries', compact('countries'));
    }

    public function createCountry()
    {
        return view('admin.master-data.countries-create');
    }

    public function storeCountry(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', 'unique:countries,code'],
            'currency_code' => ['nullable', 'string', 'max:3'],
            'currency_symbol' => ['nullable', 'string', 'max:10'],
            'phone_code' => ['nullable', 'string', 'max:20'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'date_format' => ['nullable', 'string', 'max:50'],
            'time_format' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        Country::create($validated);

        return redirect()->route('admin.master-data.countries')->with('success', 'Country created successfully.');
    }

    public function editCountry(Country $country)
    {
        return view('admin.master-data.countries-edit', compact('country'));
    }

    public function updateCountry(Request $request, Country $country)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', 'unique:countries,code,'.$country->id],
            'currency_code' => ['nullable', 'string', 'max:3'],
            'currency_symbol' => ['nullable', 'string', 'max:10'],
            'phone_code' => ['nullable', 'string', 'max:20'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'date_format' => ['nullable', 'string', 'max:50'],
            'time_format' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $country->update($validated);

        return redirect()->route('admin.master-data.countries')->with('success', 'Country updated successfully.');
    }

    public function destroyCountry(Country $country)
    {
        $country->delete();

        return redirect()->route('admin.master-data.countries')->with('success', 'Country deleted successfully.');
    }

    // States
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

    public function createState()
    {
        $countries = Country::query()->where('is_active', true)->get();

        return view('admin.master-data.states-create', compact('countries'));
    }

    public function storeState(Request $request)
    {
        $validated = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        State::create($validated);

        return redirect()->route('admin.master-data.all-states')->with('success', 'State created successfully.');
    }

    public function editState(State $state)
    {
        $countries = Country::query()->where('is_active', true)->get();

        return view('admin.master-data.states-edit', compact('state', 'countries'));
    }

    public function updateState(Request $request, State $state)
    {
        $validated = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        $state->update($validated);

        return redirect()->route('admin.master-data.all-states')->with('success', 'State updated successfully.');
    }

    public function destroyState(State $state)
    {
        $state->delete();

        return redirect()->route('admin.master-data.all-states')->with('success', 'State deleted successfully.');
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

    // Currencies
    public function currencies(Request $request)
    {
        $currencies = Currency::query()
            ->when($request->filled('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20);

        return view('admin.master-data.currencies', compact('currencies'));
    }

    public function createCurrency()
    {
        return view('admin.master-data.currencies-create');
    }

    public function storeCurrency(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:3', 'unique:currencies,code'],
            'symbol' => ['required', 'string', 'max:10'],
            'decimal_places' => ['required', 'string', 'max:1'],
            'is_active' => ['boolean'],
        ]);

        Currency::create($validated);

        return redirect()->route('admin.master-data.currencies')->with('success', 'Currency created successfully.');
    }

    public function editCurrency(Currency $currency)
    {
        return view('admin.master-data.currencies-edit', compact('currency'));
    }

    public function updateCurrency(Request $request, Currency $currency)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:3', 'unique:currencies,code,'.$currency->id],
            'symbol' => ['required', 'string', 'max:10'],
            'decimal_places' => ['required', 'string', 'max:1'],
            'is_active' => ['boolean'],
        ]);

        $currency->update($validated);

        return redirect()->route('admin.master-data.currencies')->with('success', 'Currency updated successfully.');
    }

    public function destroyCurrency(Currency $currency)
    {
        $currency->delete();

        return redirect()->route('admin.master-data.currencies')->with('success', 'Currency deleted successfully.');
    }

    // Identification Types
    public function identificationTypes(Request $request)
    {
        $types = IdentificationType::query()
            ->when($request->filled('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20);

        return view('admin.master-data.identification-types', compact('types'));
    }

    public function createIdentificationType()
    {
        return view('admin.master-data.identification-types-create');
    }

    public function storeIdentificationType(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:identification_types,code'],
            'description' => ['nullable', 'string'],
            'issuing_authority' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        IdentificationType::create($validated);

        return redirect()->route('admin.master-data.identification-types')->with('success', 'Identification type created successfully.');
    }

    public function editIdentificationType(IdentificationType $identificationType)
    {
        return view('admin.master-data.identification-types-edit', compact('identificationType'));
    }

    public function updateIdentificationType(Request $request, IdentificationType $identificationType)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:identification_types,code,'.$identificationType->id],
            'description' => ['nullable', 'string'],
            'issuing_authority' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $identificationType->update($validated);

        return redirect()->route('admin.master-data.identification-types')->with('success', 'Identification type updated successfully.');
    }

    public function destroyIdentificationType(IdentificationType $identificationType)
    {
        $identificationType->delete();

        return redirect()->route('admin.master-data.identification-types')->with('success', 'Identification type deleted successfully.');
    }
}
