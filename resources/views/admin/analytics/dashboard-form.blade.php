@extends('layouts.adminlte')

@section('page_title', __('Make a dashboard'))

@section('page_content')
    @include('admin.analytics._flash')
    <form method="POST" action="{{ route('admin.analytics.dashboards.store') }}" class="card"><div class="card-body">@csrf
        <div class="row g-2 mb-3">
            <div class="col-md-6"><label class="form-label" for="name">{{ __('Name') }}</label><input id="name" name="name" class="form-control" required maxlength="120" value="{{ old('name') }}"></div>
            <div class="col-md-6 d-flex align-items-end"><input type="hidden" name="shared" value="0"><div class="form-check"><input type="checkbox" class="form-check-input" id="shared" name="shared" value="1"><label class="form-check-label" for="shared">{{ __('Share with everyone who can open dashboards') }}</label></div></div>
        </div>
        <p class="text-muted small">{{ __('Each row is one widget. Leave the kind empty to skip a row. A KPI tile needs a KPI; a chart or table needs a subject and a figure, and can be cut by one thing or over time.') }}</p>
        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>{{ __('Kind') }}</th><th>{{ __('KPI') }}</th><th>{{ __('Title') }}</th><th>{{ __('Subject') }}</th><th>{{ __('Figure') }}</th><th>{{ __('Cut by') }}</th><th>{{ __('Over time') }}</th><th>{{ __('Drawn as') }}</th></tr></thead><tbody>
            @for($i = 0; $i < 8; $i++)<tr>
                <td><select name="widgets[{{ $i }}][type]" class="form-select form-select-sm" aria-label="{{ __('Kind') }}"><option value=""></option><option value="kpi">{{ __('KPI tile') }}</option><option value="chart">{{ __('Chart') }}</option><option value="table">{{ __('Table') }}</option></select></td>
                <td><select name="widgets[{{ $i }}][kpi]" class="form-select form-select-sm" aria-label="{{ __('KPI') }}"><option value=""></option>@foreach($kpis as $k)<option value="{{ $k->code }}">{{ __($k->name) }}</option>@endforeach</select></td>
                <td><input name="widgets[{{ $i }}][title]" class="form-control form-control-sm" maxlength="100" aria-label="{{ __('Title') }}"></td>
                <td><select name="widgets[{{ $i }}][fact]" class="form-select form-select-sm" aria-label="{{ __('Subject') }}"><option value=""></option>@foreach($catalogue as $key => $f)<option value="{{ $key }}">{{ __($f['label']) }}</option>@endforeach</select></td>
                <td><select name="widgets[{{ $i }}][measure]" class="form-select form-select-sm" aria-label="{{ __('Figure') }}"><option value=""></option>@foreach($catalogue as $key => $f)<optgroup label="{{ __($f['label']) }}">@foreach($f['measures'] as $mk => $m)<option value="{{ $mk }}">{{ __($m['label']) }}</option>@endforeach</optgroup>@endforeach</select></td>
                <td><select name="widgets[{{ $i }}][group_by]" class="form-select form-select-sm" aria-label="{{ __('Cut by') }}"><option value=""></option>@foreach(collect($catalogue)->flatMap(fn ($f) => $f['dimensions'])->map(fn ($d) => $d['label'])->unique() as $dk => $dl)<option value="{{ $dk }}">{{ __($dl) }}</option>@endforeach</select></td>
                <td><select name="widgets[{{ $i }}][grain]" class="form-select form-select-sm" aria-label="{{ __('Over time') }}"><option value=""></option><option value="day">{{ __('By day') }}</option><option value="month">{{ __('By month') }}</option><option value="year">{{ __('By year') }}</option></select></td>
                <td><select name="widgets[{{ $i }}][kind]" class="form-select form-select-sm" aria-label="{{ __('Drawn as') }}"><option value="bar">{{ __('Bars') }}</option><option value="line">{{ __('Line') }}</option></select></td></tr>@endfor</tbody></table></div>
    </div><div class="card-footer"><button class="btn btn-primary">{{ __('Save the dashboard') }}</button></div></form>
@stop
