@extends('layouts.adminlte')

@section('page_title', 'ICU Flowsheet')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ $episode->episode_number }}</h3></div>
        <div class="card-body p-0">
            <table class="table table-sm table-bordered">
                <thead><tr><th>{{ __('Time') }}</th><th>{{ __('Observations') }}</th><th>{{ __('Ventilator') }}</th><th>{{ __('Fluid Balance') }}</th></tr></thead>
                <tbody>
                    @foreach($grid as $time => $bucket)
                        <tr>
                            <td>{{ $time }}</td>
                            <td>
                                @foreach($bucket['observations'] as $obs)
                                    {{ $obs->observation_type }}: {{ $obs->value_numeric ?? $obs->value_text }}<br>
                                @endforeach
                            </td>
                            <td>
                                @foreach($bucket['ventilator'] as $v)
                                    {{ $v->event_type }}<br>
                                @endforeach
                            </td>
                            <td>
                                @foreach($bucket['fluid_balance'] as $f)
                                    {{ $f->type }}: {{ $f->volume_ml }}ml<br>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop
