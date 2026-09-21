@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Identification Types</h3>
                    <a href="{{ route('admin.master-data.index') }}" class="btn btn-secondary float-right">Back to Master Data</a>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Description</th>
                                <th>Issuing Authority</th>
                                <th>Primary</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($types as $type)
                                <tr>
                                    <td>{{ $type->id }}</td>
                                    <td>{{ $type->name }}</td>
                                    <td>{{ $type->code }}</td>
                                    <td>{{ $type->description ?? 'N/A' }}</td>
                                    <td>{{ $type->issuing_authority ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $type->is_primary ? 'primary' : 'secondary' }}">
                                            {{ $type->is_primary ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $type->is_active ? 'success' : 'danger' }}">
                                            {{ $type->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center">No identification types found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $types->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
