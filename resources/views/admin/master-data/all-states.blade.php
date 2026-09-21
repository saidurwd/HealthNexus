@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All States</h3>
                    <a href="{{ route('admin.master-data.index') }}" class="btn btn-secondary float-right">Back to Master Data</a>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Country</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($states as $state)
                                <tr>
                                    <td>{{ $state->id }}</td>
                                    <td>{{ $state->name }}</td>
                                    <td>{{ $state->code ?? 'N/A' }}</td>
                                    <td>{{ $state->country?->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $state->is_active ? 'success' : 'danger' }}">
                                            {{ $state->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center">No states found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $states->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
