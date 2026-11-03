@extends('layouts.adminlte')

@section('page_title', 'ICU Occupancy Report')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Episodes by Status') }}</h3></div>
        <div class="card-body p-0">
            <table class="table table-sm">
                <thead><tr><th>{{ __('Status') }}</th><th>{{ __('Count') }}</th></tr></thead>
                <tbody>
                    @foreach($byStatus as $status => $count)
                        <tr><td>{{ ucwords(str_replace('_', ' ', $status)) }}</td><td>{{ $count }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop
