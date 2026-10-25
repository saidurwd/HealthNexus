@extends('layouts.adminlte')

@section('page_title', 'ICU Episode — '.$episode->episode_number)

@section('page_content')
    @if($episode->patient)<x-patient-banner :patient="$episode->patient" />@endif
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $episode->patient?->full_name }} — {{ $episode->episode_number }}</h3>
            <span class="badge bg-info float-right">{{ ucwords(str_replace('_', ' ', $episode->status)) }}</span>
        </div>
        <div class="card-body">
            <p><strong>{{ __('Bed:') }}</strong> {{ $episode->icuBed?->bed_code ?? '—' }} ({{ $episode->icuUnit?->name ?? '—' }})</p>
            <p><strong>{{ __('Admitting Provider:') }}</strong> {{ $episode->admittingProvider?->name ?? '—' }}</p>
            <p><strong>{{ __('Attending Provider:') }}</strong> {{ $episode->attendingProvider?->name ?? '—' }}</p>
            <p><strong>{{ __('Admission Reason:') }}</strong> {{ $episode->admission_reason ?? '—' }}</p>
            <p><strong>{{ __('Admitted:') }}</strong> {{ $episode->admitted_at?->format('Y-m-d H:i') }}</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Clinical Workspace') }}</h3></div>
                <div class="card-body">
                    <a href="{{ route('admin.icu.episodes.assessment', $episode) }}" class="btn btn-outline-primary btn-sm mb-1">{{ __('Assessment') }}</a>
                    <a href="{{ route('admin.icu.episodes.flowsheet', $episode) }}" class="btn btn-outline-primary btn-sm mb-1">{{ __('Flowsheet') }}</a>
                    <a href="{{ route('admin.icu.discharge.create', $episode) }}" class="btn btn-outline-danger btn-sm mb-1">{{ __('Discharge / Transfer') }}</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Episode Events') }}</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm">
                        <tbody>
                            @foreach($episode->events as $event)
                                <tr>
                                    <td>{{ $event->created_at->format('Y-m-d H:i') }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $event->event_type)) }}</td>
                                    <td>{{ $event->destination }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Record Observation') }}</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.icu.episodes.observations.store', $episode) }}" class="form-inline">
                @csrf
                <input type="text" name="observation_type" class="form-control mr-2" placeholder="{{ __('Type (e.g. heart_rate)') }}" required>
                <input type="number" step="0.01" name="value_numeric" class="form-control mr-2" placeholder="{{ __('Value') }}">
                <input type="text" name="unit" class="form-control mr-2" placeholder="{{ __('Unit') }}">
                <button type="submit" class="btn btn-primary">{{ __('Record') }}</button>
            </form>
        </div>
    </div>
@stop
