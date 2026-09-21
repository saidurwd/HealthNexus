@extends('layouts.adminlte')

@section('page_title', $patient->full_name.' - Timeline')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Patient Timeline</h2>
            <a href="{{ route('admin.patients.show', $patient) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Profile
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="timeline">
                    @forelse($timeline as $item)
                        @php
                            $type = class_basename(get_class($item));
                            $colorMap = [
                                'Patient' => 'info',
                                'PatientAllergy' => 'danger',
                                'PatientHistory' => 'warning',
                                'PatientDocument' => 'primary',
                                'PatientIdentifier' => 'success',
                                'PatientContact' => 'secondary',
                                'PatientBranchRegistration' => 'info',
                                'Encounter' => 'purple',
                            ];
                            $color = $colorMap[$type] ?? 'secondary';

                            $title = '';
                            $desc = '';

                            if (isset($item->substance)) {
                                $title = 'Allergy: '.$item->substance;
                                $desc = $item->severity ? ucfirst($item->severity).' - ' : '';
                                $desc .= $item->reaction ? ucfirst($item->reaction) : '';
                            } elseif (isset($item->condition)) {
                                $title = 'Medical History: '.$item->condition;
                                $desc = $item->description ?? '';
                            } elseif (isset($item->document_type)) {
                                $title = 'Document: '.$item->file_name;
                                $desc = ucfirst(str_replace('_', ' ', $item->document_type));
                            } elseif (isset($item->identifier_value)) {
                                $title = 'Identifier: '.$item->identifier_type;
                                $desc = $item->identifier_value;
                            } elseif (isset($item->relationship)) {
                                $title = 'Contact: '.$item->name;
                                $desc = $item->relationship ?? '';
                            } else {
                                $title = $item->full_name ?? $item->name ?? 'Patient Record';
                                $desc = $item instanceof \App\Models\Patient ? 'Patient created' : '';
                            }
                        @endphp

                        <div class="mb-4 pb-4 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge bg-{{ $color }}">{{ $type }}</span>
                                    <strong class="d-block mt-1">{{ $title }}</strong>
                                    <small class="text-muted">{{ $desc }}</small>
                                </div>
                                <small class="text-muted">{{ $item->created_at?->format('M d, Y H:i') ?? '-' }}</small>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">No timeline events found.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
