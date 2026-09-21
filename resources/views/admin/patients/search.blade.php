@extends('layouts.adminlte')

@section('page_title', 'Patient Search')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Search Patients</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.patients.search') }}" class="mb-4">
                <div class="input-group">
                    <input type="text" name="q" value="{{ $search ?? '' }}" class="form-control" placeholder="Search by name, phone, patient no, national ID...">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Patient No</th>
                            <th>Name</th>
                            <th>Date of Birth</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($patients as $patient)
                            <tr>
                                <td>{{ $patient->enterprise_patient_no ?? '-' }}</td>
                                <td>{{ $patient->full_name }}</td>
                                <td>{{ $patient->date_of_birth?->format('Y-m-d') ?? '-' }}</td>
                                <td>{{ $patient->phone ?? '-' }}</td>
                                <td>{{ $patient->email ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $patient->status === 'active' ? 'bg-success' : ($patient->status === 'inactive' ? 'bg-warning' : 'bg-danger') }}">
                                        {{ ucfirst($patient->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.patients.show', $patient) }}" class="btn btn-xs btn-info">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No patients found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $patients->links() }}
            </div>
        </div>
    </div>
@stop
