@extends('layouts.adminlte')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('All States') }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.master-data.states.create') }}" class="btn btn-primary btn-sm">{{ __('Add State') }}</a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('ID') }}</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Code') }}</th>
                                <th>{{ __('Country') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Actions') }}</th>
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
                                    <td>
                                        <a href="{{ route('admin.master-data.states.edit', $state) }}" class="btn btn-sm btn-warning">{{ __('Edit') }}</a>
                                        <form action="{{ route('admin.master-data.states.destroy', $state) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">{{ __('Delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center">{{ __('No states found.') }}</td></tr>
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
