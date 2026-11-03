@extends('layouts.adminlte')

@section('page_title', 'Recovery / PACU')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Recovery — {{ $recovery->patient?->full_name }} <span class="badge bg-info">{{ ucwords(str_replace('_',' ',$recovery->status)) }}</span></h3></div>
        <div class="card-body">
            <p>Arrived: {{ $recovery->arrived_at }}</p>
            <p>Latest vitals:
                @php($latest = $recovery->surgery->encounter?->vitals()->latest('recorded_at')->first())
                {{ $latest ? $latest->recorded_at->diffForHumans() : 'None recorded' }}
            </p>
        </div>
    </div>

    @can('ot.recovery.record')
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Record Vitals') }}</h3></div>
                <form action="{{ route('admin.ot.recovery.vitals.store', $recovery) }}" method="post">
                    @csrf
                    <div class="card-body row">
                        <div class="col-md-4"><input name="temperature" class="form-control" placeholder="{{ __('Temp') }}"></div>
                        <div class="col-md-4"><input name="pulse_rate" class="form-control" placeholder="{{ __('Pulse') }}"></div>
                        <div class="col-md-4"><input name="oxygen_saturation" class="form-control" placeholder="{{ __('SpO2') }}"></div>
                    </div>
                    <div class="card-footer"><button class="btn btn-primary">{{ __('Save') }}</button></div>
                </form>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Assessment') }}</h3></div>
                <form action="{{ route('admin.ot.recovery.assessment.update', $recovery) }}" method="post">
                    @csrf
                    <div class="card-body">
                        <input name="pain_score" class="form-control mb-2" placeholder="{{ __('Pain score') }}" value="{{ $recovery->pain_score }}">
                        <input name="consciousness" class="form-control mb-2" placeholder="{{ __('Consciousness') }}" value="{{ $recovery->consciousness }}">
                        <div class="form-check">
                            <input type="hidden" name="discharge_criteria_met" value="0">
                            <input type="checkbox" name="discharge_criteria_met" value="1" class="form-check-input" id="dcm" {{ $recovery->discharge_criteria_met ? 'checked' : '' }}>
                            <label class="form-check-label" for="dcm">{{ __('Discharge criteria met') }}</label>
                        </div>
                    </div>
                    <div class="card-footer"><button class="btn btn-primary">{{ __('Save') }}</button></div>
                </form>
            </div>
        </div>
    </div>
    @endcan

    @if($recovery->status === 'in_recovery')
        @can('ot.recovery.record')
        <form action="{{ route('admin.ot.recovery.ready-for-transfer', $recovery) }}" method="post">@csrf<button class="btn btn-success">{{ __('Mark Ready For Transfer') }}</button></form>
        @endcan
    @elseif($recovery->status === 'ready_for_transfer')
        @can('ot.recovery.complete')
        <form action="{{ route('admin.ot.recovery.transfer', $recovery) }}" method="post">
            @csrf
            <select name="destination" class="form-control d-inline w-auto">
                @foreach(['ward','icu','hdu','discharge','other_facility'] as $d)<option value="{{ $d }}">{{ ucwords(str_replace('_',' ',$d)) }}</option>@endforeach
            </select>
            <button class="btn btn-success">{{ __('Complete Transfer') }}</button>
        </form>
        @endcan
    @endif
@stop
