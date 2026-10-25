@extends('layouts.adminlte')

@section('page_title', 'Theatres')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Theatres') }}</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('Complex') }}</th><th>{{ __('Capabilities') }}</th><th>{{ __('Status') }}</th></tr></thead>
                <tbody>
                    @forelse($theatres as $theatre)
                        <tr>
                            <td>{{ $theatre->theatre_code }}</td>
                            <td>{{ $theatre->name }}</td>
                            <td>{{ $theatre->complex?->name ?? '—' }}</td>
                            <td>{{ $theatre->capabilities->pluck('capability')->implode(', ') }}</td>
                            <td><span class="badge bg-info">{{ ucfirst($theatre->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">{{ __('No theatres configured.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('ot.theatre.manage')
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Add OT Complex') }}</h3></div>
                <form action="{{ route('admin.ot.complexes.store') }}" method="post">
                    @csrf
                    <div class="card-body">
                        <input name="code" class="form-control mb-2" placeholder="{{ __('Code') }}" required>
                        <input name="name" class="form-control mb-2" placeholder="{{ __('Name') }}" required>
                    </div>
                    <div class="card-footer"><button class="btn btn-primary">{{ __('Save') }}</button></div>
                </form>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Add Theatre') }}</h3></div>
                <form action="{{ route('admin.ot.theatres.store') }}" method="post">
                    @csrf
                    <div class="card-body">
                        <select name="complex_id" class="form-control mb-2">
                            @foreach($complexes as $complex)<option value="{{ $complex->id }}">{{ $complex->name }}</option>@endforeach
                        </select>
                        <input name="theatre_code" class="form-control mb-2" placeholder="{{ __('Theatre code') }}" required>
                        <input name="name" class="form-control mb-2" placeholder="{{ __('Name') }}" required>
                    </div>
                    <div class="card-footer"><button class="btn btn-primary">{{ __('Save') }}</button></div>
                </form>
            </div>
        </div>
    </div>
    @endcan
@stop
