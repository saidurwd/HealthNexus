@extends('layouts.adminlte')

@section('page_title', 'Nursing Care Plan')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Care Plan #{{ $carePlan->id }} ({{ ucfirst($carePlan->status) }})</h3>
            @can('complete', $carePlan)
                <form action="{{ route('admin.nursing.care-plans.complete', $carePlan) }}" method="post" class="float-right">@csrf<button class="btn btn-sm btn-success">{{ __('Complete') }}</button></form>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Nursing Diagnoses') }}</h3></div>
        <div class="card-body">
            <ul>@foreach($carePlan->diagnoses as $d)<li>{{ $d->diagnosis_text }} <small>({{ $d->priority }})</small></li>@endforeach</ul>
            <form action="{{ route('admin.nursing.care-plans.diagnoses.store', $carePlan) }}" method="post">
                @csrf
                <input name="diagnosis_text" class="form-control mb-2" placeholder="{{ __('Nursing diagnosis') }}" required>
                <button class="btn btn-sm btn-primary">{{ __('Add') }}</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Goals') }}</h3></div>
        <div class="card-body">
            <ul>@foreach($carePlan->goals as $g)<li>{{ $g->goal_text }} <small>({{ $g->status }})</small></li>@endforeach</ul>
            <form action="{{ route('admin.nursing.care-plans.goals.store', $carePlan) }}" method="post">
                @csrf
                <input name="goal_text" class="form-control mb-2" placeholder="{{ __('Goal') }}" required>
                <button class="btn btn-sm btn-primary">{{ __('Add') }}</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Interventions') }}</h3></div>
        <div class="card-body">
            <ul>@foreach($carePlan->interventions as $i)<li>{{ $i->intervention_type }} — {{ $i->frequency ?? 'no frequency' }} ({{ $i->status }})</li>@endforeach</ul>
            <form action="{{ route('admin.nursing.care-plans.interventions.store', $carePlan) }}" method="post">
                @csrf
                <input name="intervention_type" class="form-control mb-2" placeholder="{{ __('Intervention') }}" required>
                <input name="frequency" class="form-control mb-2" placeholder="{{ __('Frequency') }}">
                <button class="btn btn-sm btn-primary">{{ __('Add') }}</button>
            </form>
        </div>
    </div>
@stop
