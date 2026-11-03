@extends('layouts.adminlte')

@section('title', 'Search')

@section('content_header')
    <h1>{{ __('Search') }}</h1>
@stop

@section('content')
    <form method="GET" action="{{ route('admin.search') }}" class="mb-3">
        <div class="input-group">
            <input type="search" name="q" value="{{ $term }}" class="form-control form-control-lg" autofocus autocomplete="off"
                   placeholder="{{ __('MRN, mobile number, NID, name (Bangla or English), or scan a barcode') }}">
            <button class="btn btn-primary" type="submit">{{ __('Search') }}</button>
        </div>
    </form>

    @if($term !== '')
        <div class="card">
            <div class="list-group list-group-flush">
                @forelse($results as $result)
                    <a href="{{ $result['url'] }}" class="list-group-item list-group-item-action d-flex justify-content-between">
                        <span><strong>{{ $result['label'] }}</strong> <span class="text-muted">{{ $result['number'] }}</span></span>
                        <span class="badge bg-secondary">{{ $result['matched_on'] }}</span>
                    </a>
                @empty
                    <div class="list-group-item text-muted">No match for “{{ $term }}”.</div>
                @endforelse
            </div>
        </div>
    @endif
@stop
