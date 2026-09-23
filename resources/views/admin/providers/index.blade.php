@extends('layouts.adminlte')

@section('page_title', 'Providers')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Providers</h2>
            <a href="{{ route('admin.providers.create') }}" class="btn btn-primary">
                <i class="bi bi-plus me-1"></i>Add Provider
            </a>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Department</th>
                                <th>Specialty</th>
                                <th>Linked User</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($providers as $provider)
                                <tr>
                                    <td>{{ $provider->name }}</td>
                                    <td>{{ ucfirst($provider->provider_type) }}</td>
                                    <td>{{ $provider->department->name ?? '-' }}</td>
                                    <td>{{ $provider->specialty->name ?? '-' }}</td>
                                    <td>{{ $provider->user->name ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $provider->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($provider->status) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.providers.edit', $provider) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                                        <form action="{{ route('admin.providers.destroy', $provider) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this provider?')">
                                            @csrf
                                            @method('delete')
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No providers found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($providers->hasPages())
                <div class="card-footer">{{ $providers->links() }}</div>
            @endif
        </div>
    </div>
@endsection
