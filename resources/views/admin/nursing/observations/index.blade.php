@extends('layouts.adminlte')

@section('page_title', 'Vitals & Observations')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Record Vitals — {{ $episode->patient?->full_name }}</h3></div>
        <form action="{{ route('admin.nursing.vitals.store', $episode) }}" method="post">
            @csrf
            <div class="card-body row">
                <div class="col-md-2"><input name="temperature" class="form-control" placeholder="{{ __('Temp') }}"></div>
                <div class="col-md-2"><input name="systolic" class="form-control" placeholder="{{ __('Systolic') }}"></div>
                <div class="col-md-2"><input name="diastolic" class="form-control" placeholder="{{ __('Diastolic') }}"></div>
                <div class="col-md-2"><input name="pulse_rate" class="form-control" placeholder="{{ __('Pulse') }}"></div>
                <div class="col-md-2"><input name="respiratory_rate" class="form-control" placeholder="{{ __('RR') }}"></div>
                <div class="col-md-2"><input name="oxygen_saturation" class="form-control" placeholder="{{ __('SpO2') }}"></div>
            </div>
            <div class="card-footer"><button class="btn btn-primary">{{ __('Save Vitals') }}</button></div>
        </form>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Record Observation (pain / glucose / consciousness / custom)') }}</h3></div>
        <form action="{{ route('admin.nursing.observations.store', $episode) }}" method="post">
            @csrf
            <div class="card-body row">
                <div class="col-md-4"><input name="observation_type" class="form-control" placeholder="{{ __('Type e.g. blood_glucose') }}" required></div>
                <div class="col-md-4"><input name="value" class="form-control" placeholder="{{ __('Value') }}" required></div>
                <div class="col-md-4"><input name="unit" class="form-control" placeholder="{{ __('Unit') }}"></div>
            </div>
            <div class="card-footer"><button class="btn btn-primary">{{ __('Save Observation') }}</button></div>
        </form>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Vitals History') }}</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>{{ __('Recorded') }}</th><th>{{ __('Temp') }}</th><th>{{ __('BP') }}</th><th>{{ __('Pulse') }}</th><th>{{ __('RR') }}</th><th>{{ __('SpO2') }}</th></tr></thead>
                <tbody>
                    @forelse($vitals as $v)
                        <tr>
                            <td>{{ $v->recorded_at }}</td><td>{{ $v->temperature }}</td>
                            <td>{{ $v->systolic }}/{{ $v->diastolic }}</td><td>{{ $v->pulse_rate }}</td>
                            <td>{{ $v->respiratory_rate }}</td><td>{{ $v->oxygen_saturation }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">{{ __('No vitals recorded.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Observations') }}</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>{{ __('Observed') }}</th><th>{{ __('Type') }}</th><th>{{ __('Value') }}</th><th>{{ __('Status') }}</th></tr></thead>
                <tbody>
                    @forelse($observations as $o)
                        <tr><td>{{ $o->observed_at }}</td><td>{{ $o->observation_type }}</td><td>{{ $o->value }} {{ $o->unit }}</td><td>{{ $o->status }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-center">{{ __('No observations recorded.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
