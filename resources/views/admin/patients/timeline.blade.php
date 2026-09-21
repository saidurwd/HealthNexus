@extends('layouts.adminlte')

@section('page_title', $patient->full_name.' - Timeline')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Patient Timeline</h3>
            <div class="card-tools">
                <a href="{{ route('admin.patients.show', $patient) }}" class="btn btn-secondary btn-sm">Back to Profile</a>
            </div>
        </div>
        <div class="card-body">
            <div class="timeline">
                @forelse($timeline as $item)
                    <div class="mb-4 pb-4 border-bottom">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $item->created_at?->format('Y-m-d H:i') ?? '-' }}</strong>
                            <span class="badge bg-secondary">{{ class_basename(get_class($item)) }}</span>
                        </div>
                        <div class="mt-2">
                            @if (isset($item->substance))
                                <strong>Allergy:</strong> {{ $item->substance }}
                                @if($item->severity)
                                    ({{ ucfirst($item->severity) }})
                                @endif
                            @elseif (isset($item->condition))
                                <strong>Medical History:</strong> {{ $item->condition }}
                            @elseif (isset($item->document_type))
                                <strong>Document:</strong> {{ $item->file_name }} ({{ $item->document_type }})
                            @elseif (isset($item->identifier_value))
                                <strong>Identifier:</strong> {{ $item->identifier_type }} - {{ $item->identifier_value }}
                            @elseif (isset($item->relationship))
                                <strong>Contact:</strong> {{ $item->name }} ({{ $item->relationship }})
                            @else
                                <strong>{{ $item->full_name ?? $item->name ?? 'Patient Record' }}</strong>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-muted">No timeline events found.</p>
                @endforelse
            </div>
        </div>
    </div>
@stop
