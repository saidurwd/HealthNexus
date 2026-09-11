@extends('adminlte::page')

@section('title', 'Encounters')

@section('content_header')
    <h1>Encounters</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">All Encounters</h3>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Started At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($encounters as $encounter)
                        <tr>
                            <td>{{ $encounter->id }}</td>
                            <td>{{ $encounter->patient->full_name ?? 'N/A' }}</td>
                            <td>{{ $encounter->encounter_type }}</td>
                            <td>{{ $encounter->status }}</td>
                            <td>{{ $encounter->started_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.encounters.show', $encounter) }}" class="btn btn-sm btn-info">View</a>
                                <a href="{{ route('admin.encounters.edit', $encounter) }}" class="btn btn-sm btn-warning">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No encounters found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $encounters->links() }}
        </div>
    </div>
@stop
