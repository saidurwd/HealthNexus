@extends('layouts.adminlte')

@section('page_title', 'ICU Bed Board')

@section('page_content')
    <div class="row">
        @forelse($board as $row)
            <div class="col-md-3 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <h5>{{ $row['bed']->bed_code }}</h5>
                        <div class="text-muted">{{ $row['ward']?->name ?? 'Unassigned unit' }}</div>
                        <span class="badge bg-{{ $row['episode'] ? 'danger' : 'success' }}">{{ $row['episode'] ? 'Occupied' : ucwords($row['status']) }}</span>
                        @if($row['patient'])
                            <div class="mt-2">{{ $row['patient']->full_name }}</div>
                            <a href="{{ route('admin.icu.episodes.show', $row['episode']) }}" class="btn btn-xs btn-info mt-1">Open</a>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><p class="text-center">No ICU-capable beds configured.</p></div>
        @endforelse
    </div>
@stop
