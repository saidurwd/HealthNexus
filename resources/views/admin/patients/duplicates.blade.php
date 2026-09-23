@extends('layouts.adminlte')

@section('page_title', 'Duplicate Review Queue')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Duplicate Review Queue</h2>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Patient A</th>
                                <th>Patient B</th>
                                <th class="text-center">Score</th>
                                <th>Classification</th>
                                <th>Match Reasons</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($candidates as $candidate)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.patients.show', $candidate->patientA) }}">{{ $candidate->patientA?->full_name }}</a>
                                        <br><small class="text-muted">{{ $candidate->patientA?->enterprise_patient_no }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.patients.show', $candidate->patientB) }}">{{ $candidate->patientB?->full_name }}</a>
                                        <br><small class="text-muted">{{ $candidate->patientB?->enterprise_patient_no }}</small>
                                    </td>
                                    <td class="text-center">{{ $candidate->score }}%</td>
                                    <td>
                                        <span class="badge {{ $candidate->classification === 'strong_match' ? 'bg-danger' : 'bg-warning text-dark' }}">
                                            {{ str_replace('_', ' ', ucfirst($candidate->classification)) }}
                                        </span>
                                    </td>
                                    <td><small class="text-muted">{{ implode(', ', $candidate->match_reasons ?? []) }}</small></td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">{{ str_replace('_', ' ', ucfirst($candidate->status)) }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($candidate->status === 'pending')
                                            <form action="{{ route('admin.patients.duplicates.review', $candidate) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('put')
                                                <input type="hidden" name="status" value="confirmed_duplicate">
                                                <button class="btn btn-sm btn-outline-danger" title="Confirm duplicate">Confirm</button>
                                            </form>
                                            <form action="{{ route('admin.patients.duplicates.review', $candidate) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('put')
                                                <input type="hidden" name="status" value="not_duplicate">
                                                <button class="btn btn-sm btn-outline-success" title="Not a duplicate">Not Duplicate</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No duplicate candidates pending review.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                {{ $candidates->links() }}
            </div>
        </div>
    </div>
@endsection
