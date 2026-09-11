@extends('layouts.adminlte')

@section('page_title', $patient->full_name)

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Patient Details</h3>
            <div class="card-tools">
                <a href="{{ route('admin.patients.edit', $patient) }}" class="btn btn-warning btn-sm">Edit</a>
                <a href="{{ route('admin.patients.index') }}" class="btn btn-secondary btn-sm">Back</a>
            </div>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Patient No</dt>
                <dd class="col-sm-9">{{ $patient->enterprise_patient_no }}</dd>

                <dt class="col-sm-3">National ID</dt>
                <dd class="col-sm-9">{{ $patient->national_identifier ?? '-' }}</dd>

                <dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9">{{ $patient->full_name }}</dd>

                <dt class="col-sm-3">Date of Birth</dt>
                <dd class="col-sm-9">{{ $patient->date_of_birth?->format('Y-m-d') ?? '-' }}</dd>

                <dt class="col-sm-3">Sex</dt>
                <dd class="col-sm-9">{{ $patient->sex ?? '-' }}</dd>

                <dt class="col-sm-3">Blood Group</dt>
                <dd class="col-sm-9">{{ $patient->blood_group ?? '-' }}</dd>

                <dt class="col-sm-3">Phone</dt>
                <dd class="col-sm-9">{{ $patient->phone ?? '-' }}</dd>

                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9">{{ $patient->email ?? '-' }}</dd>

                <dt class="col-sm-3">Address</dt>
                <dd class="col-sm-9">{{ $patient->address ?? '-' }}</dd>

                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9">
                    <span class="badge {{ $patient->status === 'active' ? 'bg-success' : 'bg-danger' }}">
                        {{ ucfirst($patient->status) }}
                    </span>
                </dd>

                <dt class="col-sm-3">Created</dt>
                <dd class="col-sm-9">{{ $patient->created_at->format('Y-m-d H:i') }}</dd>
            </dl>
        </div>
    </div>
@stop
