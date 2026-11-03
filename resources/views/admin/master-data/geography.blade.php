@extends('layouts.adminlte')

@section('page_title', __('Geography'))

@section('page_content')
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    @if (session('import_skipped'))<div class="alert alert-warning small"><strong>{{ __('Rows not imported') }}</strong><ul class="mb-0">@foreach(session('import_skipped') as $why)<li>{{ $why }}</li>@endforeach</ul></div>@endif
    @php $N = fn ($v) => \App\Support\Dashboard\Numbers::number($v); @endphp
    <x-ui.dashboard :question="__('Where do our patients come from, and is our list of places complete?')" :audience="__('For the records officer, the director and the planning team')" :as-of="$asOf">
        <div class="row row-cols-2 row-cols-md-4 row-cols-xl-5 g-2 mb-3">
            <x-ui.kpi-tile :label="__('Divisions')" :value="$s['divisions']" unit="count" icon="bi-map" variant="secondary" good-direction="none" />
            <x-ui.kpi-tile :label="__('Districts')" :value="$s['districts']" unit="count" icon="bi-pin-map" variant="secondary" good-direction="none" />
            <x-ui.kpi-tile :label="__('Upazilas')" :value="$s['upazilas']" unit="count" icon="bi-geo" variant="secondary" good-direction="none" />
            <x-ui.kpi-tile :label="__('Unions')" :value="$s['unions']" unit="count" icon="bi-geo-alt" :variant="$s['unions'] > 0 ? 'secondary' : 'warning'" good-direction="up" :note="$s['unions'] > 0 ? null : __('None yet: add them or import the national list.')" />
            <x-ui.kpi-tile :label="__('Patients with a home district')" :value="$s['with_place_percent']" unit="percent" icon="bi-people" variant="primary" good-direction="up" :note="__(':a of :b patients', ['a' => $N($s['with_place']), 'b' => $N($s['patients'])])" />
            <x-ui.kpi-tile :label="__('Districts our patients come from')" :value="$s['districts_served']" unit="count" icon="bi-compass" variant="info" good-direction="none" />
            <x-ui.kpi-tile :label="__('Upazilas in use with no unions listed')" :value="$s['upazilas_without_unions']" unit="count" icon="bi-exclamation-circle" variant="warning" good-direction="down" />
        </div>
        <div class="row g-3 mb-3">
            <div class="col-lg-7"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Home districts of our patients (top 15)') }}</h3></div><div class="card-body">
                @if($s['by_district'])<x-ui.chart kind="hbar" :rows="$s['by_district']" x="district" :series="[['key' => 'patients', 'label' => 'Patients']]" />
                @else<x-ui.empty-state :title="__('No patient has a home district recorded yet.')" :hint="__('Choose the district when adding a permanent address to a patient.')" icon="bi-pin-map" />@endif
            </div></div></div>
            <div class="col-lg-5"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('By division') }}</h3></div><div class="card-body">
                @if($s['by_division'])<x-ui.chart kind="hbar" :rows="$s['by_division']" x="division" :series="[['key' => 'patients', 'label' => 'Patients']]" />
                @else<x-ui.empty-state :title="__('Nothing to show yet.')" icon="bi-map" />@endif
            </div></div></div>
        </div>
    </x-ui.dashboard>

    <div class="card mb-3"><div class="card-header d-flex flex-wrap gap-2 align-items-center"><h3 class="card-title mb-0">{{ __('Unions') }}</h3>
        <form method="GET" class="ms-auto d-flex gap-1"><input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="{{ __('Search by name') }}" aria-label="{{ __('Search by name') }}"><button class="btn btn-sm btn-outline-secondary">{{ __('Search') }}</button></form></div>
        <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Union') }}</th><th>{{ __('Upazila') }}</th><th>{{ __('District') }}</th><th>{{ __('Added by') }}</th></tr></thead><tbody>
            @forelse($unions as $u)<tr><td>{{ $u->name_en }}@if($u->name_bn) · {{ $u->name_bn }}@endif</td><td>{{ $u->upazila?->displayName() }}</td><td>{{ $u->upazila?->district?->displayName() }}</td><td>{{ $u->source === 'import' ? __('Imported list') : __('Typed in') }}</td></tr>
            @empty<tr><td colspan="4"><x-ui.empty-state :title="__('No unions are listed yet.')" :hint="__('The national list of unions was not part of the data this system started with. Add the ones your patients come from, or import a list.')" icon="bi-geo-alt" /></td></tr>@endforelse
        </tbody></table></div><div class="card-footer">{{ $unions->links() }}</div></div>

    @if($canManage)
    <div class="row g-3">
        <div class="col-lg-6"><form method="POST" action="{{ route('admin.master-data.geography.unions.store') }}" class="card h-100">@csrf
            <div class="card-header"><h3 class="card-title">{{ __('Add a union') }}</h3></div>
            <div class="card-body row g-2">
                <div class="col-md-6"><label class="form-label" for="g-division">{{ __('Division') }}</label><x-ui.geo-select level="division" name="division_id" id="g-division" /></div>
                <div class="col-md-6"><label class="form-label" for="g-district">{{ __('District') }}</label><x-ui.geo-select level="district" name="district_id" id="g-district" depends-on="#g-division" /></div>
                <div class="col-12"><label class="form-label" for="g-upazila">{{ __('Upazila') }}</label><x-ui.geo-select level="upazila" name="upazila_id" id="g-upazila" depends-on="#g-district" required /></div>
                <div class="col-md-6"><label class="form-label" for="g-name-en">{{ __('Name in English') }}</label><input id="g-name-en" name="name_en" class="form-control" maxlength="80" required></div>
                <div class="col-md-6"><label class="form-label" for="g-name-bn">{{ __('Name in Bangla') }}</label><input id="g-name-bn" name="name_bn" class="form-control" maxlength="80"></div>
            </div>
            <div class="card-footer"><button class="btn btn-primary">{{ __('Save the union') }}</button></div></form></div>
        <div class="col-lg-6"><form method="POST" action="{{ route('admin.master-data.geography.unions.import') }}" enctype="multipart/form-data" class="card h-100">@csrf
            <div class="card-header"><h3 class="card-title">{{ __('Import a list of unions') }}</h3></div>
            <div class="card-body"><p class="small text-muted">{{ __('A CSV file whose first line names the columns: division, district, upazila, union_en, union_bn (and code, if you have it). A row whose upazila cannot be found is reported and skipped.') }}</p>
                <label class="form-label" for="g-file">{{ __('CSV file') }}</label><input id="g-file" type="file" name="file" accept=".csv,text/csv" class="form-control" required></div>
            <div class="card-footer"><button class="btn btn-outline-primary">{{ __('Import') }}</button></div></form></div>
    </div>
    @endif
@stop
