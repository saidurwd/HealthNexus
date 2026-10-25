@extends('layouts.adminlte')

@section('page_title', 'ICU Admission Requests')

@section('page_content')
    <a href="{{ route('admin.icu.admission-requests.create') }}" class="btn btn-primary mb-2">New Request</a>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Patient</th><th>Priority</th><th>Emergency</th><th>Status</th><th>Requested</th><th></th></tr></thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr>
                            <td>{{ $req->patient?->full_name }}</td>
                            <td>{{ ucwords($req->priority) }}</td>
                            <td>{{ $req->is_emergency ? 'Yes' : 'No' }}</td>
                            <td><span class="badge bg-info">{{ ucwords(str_replace('_', ' ', $req->status)) }}</span></td>
                            <td>{{ $req->created_at->diffForHumans() }}</td>
                            <td><a href="{{ route('admin.icu.admission-requests.show', $req) }}" class="btn btn-xs btn-info">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No admission requests.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $requests->links() }}
@stop
