@extends('layouts.adminlte')

@section('page_title', 'Studies')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Studies</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by accession number...">
            </form>
            <table class="table table-striped">
                <thead><tr><th>Accession #</th><th>Patient</th><th>Modality</th><th>Status</th><th>Study Date</th><th></th></tr></thead>
                <tbody>
                    @forelse($studies as $study)
                        <tr>
                            <td>{{ $study->accession_number }}</td>
                            <td>{{ $study->patient?->full_name }}</td>
                            <td>{{ $study->modality }}</td>
                            <td><span class="badge {{ $study->isReconciled() ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($study->study_status) }}</span></td>
                            <td>{{ $study->study_date?->format('Y-m-d') }}</td>
                            <td><a href="{{ route('admin.radiology.studies.show', $study) }}" class="btn btn-xs btn-info">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No studies found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $studies->links() }}</div>
    </div>
@stop
