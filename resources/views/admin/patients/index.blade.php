@extends('layouts.adminlte')

@section('page_title', 'Patients')

@section('content_top_nav_left')
    <a href="{{ route('admin.patients.create') }}" class="btn btn-primary btn-sm">New Patient</a>
@stop

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Patients</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Patient No</th>
                        <th>Name</th>
                        <th>Sex</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($patients as $patient)
                        <tr>
                            <td>{{ $patient->enterprise_patient_no }}</td>
                            <td>{{ $patient->full_name }}</td>
                            <td>{{ $patient->sex }}</td>
                            <td>{{ $patient->phone ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $patient->status === 'active' ? 'bg-success' : 'bg-danger' }}">
                                    {{ ucfirst($patient->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.patients.show', $patient) }}" class="btn btn-xs btn-info">View</a>
                                <a href="{{ route('admin.patients.edit', $patient) }}" class="btn btn-xs btn-warning">Edit</a>
                                <form action="{{ route('admin.patients.destroy', $patient) }}" method="post" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-xs btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No patients found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $patients->links() }}
        </div>
    </div>
@stop
