@extends('layouts.adminlte')

@section('page_title', 'Search Patients')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">{{ __('Patient Search') }}</h2>
            <a href="{{ route('admin.patients.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Patient List') }}
            </a>
        </div>

        <form method="GET" action="{{ route('admin.patients.search') }}" class="mb-4">
            <div class="input-group input-group-lg">
                <input type="text" name="q" class="form-control" placeholder="{{ __('Search by name, patient ID, phone, or national ID...') }}" value="{{ $search ?? '' }}">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-search me-1"></i>{{ __('Search') }}
                </button>
                @if(!empty($search))
                    <a href="{{ route('admin.patients.search') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x me-1"></i>{{ __('Clear') }}
                    </a>
                @endif
            </div>
        </form>

        @if(!empty($search) && $patients->isEmpty())
            <div class="alert alert-info">
                No patients found matching "{{ $search }}".
            </div>
        @endif

        @if($patients->isNotEmpty())
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                                <thead class="table-secondary">
                                <tr>
                                    <th>{{ __('Patient') }}</th>
                                    <th>{{ __('Patient No') }}</th>
                                    <th>{{ __('Gender') }}</th>
                                    <th>{{ __('Age') }}</th>
                                    <th>{{ __('Phone') }}</th>
                                    <th class="text-center">{{ __('Status') }}</th>
                                    <th class="text-end">{{ __('Created') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($patients as $patient)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.patients.show', $patient) }}" class="text-decoration-none">
                                                <strong>{{ $patient->full_name }}</strong>
                                            </a>
                                            @if($patient->national_identifier)
                                                <br><small class="text-muted">{{ $patient->national_identifier }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $patient->enterprise_patient_no ?? '-' }}</td>
                                        <td>{{ $patient->sex ? ['M' => 'Male', 'F' => 'Female', 'O' => 'Other'][$patient->sex] : '-' }}</td>
                                        <td>
                                            @if($patient->date_of_birth)
                                                {{ \Carbon\Carbon::parse($patient->date_of_birth)->age }} yrs
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $patient->phone ?? '-' }}</td>
                                        <td class="text-center">
                                            @php
                                                $statusClass = match($patient->status) {
                                                    'active' => 'bg-success',
                                                    'inactive' => 'bg-warning text-dark',
                                                    'deceased' => 'bg-danger',
                                                    default => 'bg-secondary',
                                                };
                                            @endphp
                                            <span class="badge {{ $statusClass }}">{{ ucfirst($patient->status) }}</span>
                                        </td>
                                        <td class="text-end"><small class="text-muted">{{ $patient->created_at->format('M d, Y') }}</small></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($patients->hasPages())
                    <div class="card-footer">
                        {{ $patients->links() }}
                    </div>
                @endif
            </div>
        @endif
    </div>
@endsection
