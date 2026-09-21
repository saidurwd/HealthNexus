@extends('layouts.adminlte')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Countries</h3>
                    <a href="{{ route('admin.master-data.countries.create') }}" class="btn btn-primary float-right">Add Country</a>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Currency</th>
                                <th>Phone Code</th>
                                <th>Timezone</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($countries as $country)
                                <tr>
                                    <td>{{ $country->id }}</td>
                                    <td>{{ $country->name }}</td>
                                    <td>{{ $country->code }}</td>
                                    <td>{{ $country->currency_code }} ({{ $country->currency_symbol }})</td>
                                    <td>{{ $country->phone_code ?? 'N/A' }}</td>
                                    <td>{{ $country->timezone ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $country->is_active ? 'success' : 'danger' }}">
                                            {{ $country->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.master-data.countries.edit', $country) }}" class="btn btn-sm btn-warning">Edit</a>
                                        <form action="{{ route('admin.master-data.countries.destroy', $country) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center">No countries found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $countries->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
