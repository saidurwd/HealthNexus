@extends('layouts.adminlte')

@section('page_title', __('Downtime kit'))

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Paper forms — print these before you need them') }}</h3></div>
        <ul class="list-group list-group-flush">
            @foreach($forms as $key => $title)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    {{ __($title) }}
                    <span>
                        <a class="btn btn-sm btn-outline-primary" target="_blank" href="{{ route('admin.downtime.form', [$key, 'lang' => 'en+bn']) }}">বাংলা + English</a>
                        <a class="btn btn-sm btn-outline-secondary" target="_blank" href="{{ route('admin.downtime.form', [$key, 'lang' => 'bn']) }}">বাংলা</a>
                        <a class="btn btn-sm btn-outline-secondary" target="_blank" href="{{ route('admin.downtime.form', [$key, 'lang' => 'en']) }}">English</a>
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Inpatient census') }}</h3></div>
        <div class="card-body">
            @if($latest)
                {{ __('Last generated') }}: <strong>{{ \Illuminate\Support\Carbon::parse($latest->generated_at)->timezone(config('documents.timezone'))->format('d M Y H:i') }}</strong>
                — {{ $latest->patient_count }} {{ __('inpatients') }}.
                <a class="btn btn-sm btn-primary ms-2" target="_blank" href="{{ route('admin.downtime.census') }}">{{ __('Open and print') }}</a>
            @else
                <x-ui.empty-state :title="__('No census has been generated yet.')" :hint="__('It is produced automatically every few hours while patients are admitted.')" />
            @endif
        </div>
    </div>
@stop
