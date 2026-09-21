@extends('layouts.adminlte')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Currencies</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.master-data.currencies.create') }}" class="btn btn-primary btn-sm">Add Currency</a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Symbol</th>
                                <th>Decimal Places</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($currencies as $currency)
                                <tr>
                                    <td>{{ $currency->id }}</td>
                                    <td>{{ $currency->name }}</td>
                                    <td>{{ $currency->code }}</td>
                                    <td>{{ $currency->symbol }}</td>
                                    <td>{{ $currency->decimal_places }}</td>
                                    <td>
                                        <span class="badge bg-{{ $currency->is_active ? 'success' : 'danger' }}">
                                            {{ $currency->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.master-data.currencies.edit', $currency) }}" class="btn btn-sm btn-warning">Edit</a>
                                        <form action="{{ route('admin.master-data.currencies.destroy', $currency) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center">No currencies found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $currencies->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
