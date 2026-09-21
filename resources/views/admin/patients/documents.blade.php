@extends('layouts.adminlte')

@section('page_title', $patient->full_name.' - Documents')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Patient Documents</h3>
            <div class="card-tools">
                <a href="{{ route('admin.patients.show', $patient) }}" class="btn btn-secondary btn-sm">Back to Profile</a>
            </div>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <form method="POST" action="{{ route('admin.patients.documents.upload', $patient) }}" enctype="multipart/form-data" class="d-flex gap-2">
                    @csrf
                    <input type="file" name="document" class="form-control" required>
                    <select name="document_type" class="form-select" required>
                        <option value="">Document Type</option>
                        <option value="lab_report">Lab Report</option>
                        <option value="prescription">Prescription</option>
                        <option value="referral">Referral</option>
                        <option value="id_proof">ID Proof</option>
                        <option value="insurance">Insurance</option>
                        <option value="other">Other</option>
                    </select>
                    <input type="text" name="description" class="form-control" placeholder="Description">
                    <button type="submit" class="btn btn-primary">Upload</button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Description</th>
                            <th>Uploaded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($documents as $document)
                            <tr>
                                <td>{{ $document->file_name }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $document->document_type)) }}</td>
                                <td>{{ $document->file_size ? number_format($document->file_size / 1024, 2).' KB' : '-' }}</td>
                                <td>{{ $document->description ?? '-' }}</td>
                                <td>{{ $document->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    <a href="{{ $document->url }}" target="_blank" class="btn btn-xs btn-info">View</a>
                                    <form action="{{ route('admin.patients.documents.delete', [$patient, $document]) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                        @csrf
                                        @method('delete')
                                        <button class="btn btn-xs btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No documents found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $documents->links() }}
            </div>
        </div>
    </div>
@stop
