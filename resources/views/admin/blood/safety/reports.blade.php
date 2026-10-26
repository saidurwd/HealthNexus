@extends('layouts.adminlte')

@section('page_title', __('Blood use and wastage'))

@section('page_content')
    <form method="GET" class="d-flex gap-2 mb-3 align-items-center"><label class="mb-0" for="days">{{ __('Period') }}</label>
        <select name="days" id="days" class="form-select w-auto" onchange="this.form.submit()">@foreach([7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days', 180 => 'Last 180 days'] as $v => $l)<option value="{{ $v }}" @selected($days === $v)>{{ __($l) }}</option>@endforeach</select></form>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>{{ __('Component') }}</th><th class="text-end">{{ __('Received') }}</th><th class="text-end">{{ __('Issued') }}</th><th class="text-end">{{ __('Transfused') }}</th><th class="text-end">{{ __('Discarded') }}</th><th class="text-end">{{ __('Expired') }}</th><th class="text-end">{{ __('Wastage %') }}</th><th>{{ __('Why discarded') }}</th></tr></thead><tbody>
        @foreach($summary as $component => $row)
            <tr><td>{{ __($labels[$component]) }}</td><td class="text-end">{{ $row['received'] }}</td><td class="text-end">{{ $row['issued'] }}</td><td class="text-end">{{ $row['transfused'] }}</td><td class="text-end">{{ $row['discarded'] }}</td><td class="text-end">{{ $row['expired'] }}</td><td class="text-end {{ $row['wastage_percent'] > 10 ? 'text-danger' : '' }}">{{ $row['wastage_percent'] }}</td>
                <td class="small">@foreach($row['discard_reasons'] as $reason => $n){{ __(\Illuminate\Support\Str::headline($reason)) }} {{ $n }}; @endforeach</td></tr>
        @endforeach
        </tbody></table></div></div>
@stop
