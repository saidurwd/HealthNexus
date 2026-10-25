@extends('layouts.adminlte')

@section('page_title', $patient->full_name.' - Face Sheet')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
            <h2 class="mb-0">{{ __('Patient Face Sheet') }}</h2>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>{{ __('Print') }}</button>
                <a href="{{ route('admin.patients.show', $patient) }}" class="btn btn-outline-secondary">{{ __('Back to Profile') }}</a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h3 class="mb-1">{{ $patient->display_name }}</h3>
                        <p class="text-muted mb-0">{{ $patient->enterprise_patient_no }}</p>
                    </div>
                    <img src="{{ route('admin.patients.barcode', $patient) }}" alt="{{ __('Patient barcode') }}" style="width:120px;height:120px;">
                </div>

                <div class="row g-3">
                    <div class="col-md-4"><strong>{{ __('Date of Birth:') }}</strong> {{ $patient->date_of_birth?->format('Y-m-d') ?? '-' }}</div>
                    <div class="col-md-4"><strong>{{ __('Sex:') }}</strong> {{ $patient->sex ?? '-' }}</div>
                    <div class="col-md-4"><strong>{{ __('Blood Group:') }}</strong> {{ $patient->blood_group ?? '-' }}</div>
                    <div class="col-md-4"><strong>{{ __('Phone:') }}</strong> {{ $patient->phone ?? '-' }}</div>
                    <div class="col-md-8"><strong>{{ __('Address:') }}</strong> {{ $patient->address ?? '-' }}</div>
                </div>

                @if($patient->allergies->isNotEmpty())
                    <hr>
                    <h5>{{ __('Allergies') }}</h5>
                    <ul>
                        @foreach($patient->allergies as $allergy)
                            <li>{{ $allergy->substance }} ({{ $allergy->severity }})</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endsection
