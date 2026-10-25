@extends('layouts.adminlte')

@section('page_title', $patient->full_name.' - Documents')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">{{ __('Patient Documents') }}</h2>
            <a href="{{ route('admin.patients.show', $patient) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to Profile') }}
            </a>
        </div>

        <div class="card">
            <div class="card-header bg-secondary text-white">
                <ul class="nav nav-pills card-header-pills" id="documentTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload" type="button" role="tab">
                            <i class="bi bi-upload me-1"></i>{{ __('Upload Document') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="list-tab" data-bs-toggle="tab" data-bs-target="#list" type="button" role="tab">
                            <i class="bi bi-list-ul me-1"></i>{{ __('Document List') }}
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                        <div class="tab-pane fade show active" id="upload" role="tabpanel">
                        <form method="POST" action="{{ route('admin.patients.documents.upload', $patient) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">{{ __('Document File *') }}</label>
                                    <input type="file" name="document" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">{{ __('Document Type *') }}</label>
                                    <select name="document_type" class="form-select" required>
                                        <option value="">{{ __('Select Type') }}</option>
                                        <option value="lab_report">{{ __('Lab Report') }}</option>
                                        <option value="prescription">{{ __('Prescription') }}</option>
                                        <option value="referral">{{ __('Referral') }}</option>
                                        <option value="id_proof">{{ __('ID Proof') }}</option>
                                        <option value="insurance">{{ __('Insurance') }}</option>
                                        <option value="other">{{ __('Other') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">{{ __('Description') }}</label>
                                    <input type="text" name="description" class="form-control" placeholder="{{ __('Brief description...') }}">
                                </div>
                                <div class="col-md-1">
                                    <button type="submit" class="btn btn-primary w-100">{{ __('Upload') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="list" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-secondary">
                                    <tr>
                                        <th>{{ __('File Name') }}</th>
                                        <th>{{ __('Type') }}</th>
                                        <th class="text-end">{{ __('Size') }}</th>
                                        <th>{{ __('Uploaded') }}</th>
                                        <th class="text-center">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($documents as $document)
                                        <tr>
                                            <td>
                                                <i class="bi bi-file-earmark-text me-2"></i>{{ $document->file_name }}
                                            </td>
                                            <td>{{ ucfirst(str_replace('_', ' ', $document->document_type)) }}</td>
                                            <td class="text-end">{{ $document->file_size ? number_format($document->file_size / 1024, 2).' KB' : '-' }}</td>
                                            <td><small class="text-muted">{{ $document->created_at->format('M d, Y') }}</small></td>
                                            <td class="text-center">
                                                <a href="{{ $document->url }}" target="_blank" class="btn btn-sm btn-outline-info" title="{{ __('View') }}">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <form action="{{ route('admin.patients.documents.delete', [$patient, $document]) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this document?')">
                                                    @csrf
                                                    @method('delete')
                                                    <button class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">{{ __('No documents found.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($documents->hasPages())
                            <div class="mt-3">
                                {{ $documents->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
