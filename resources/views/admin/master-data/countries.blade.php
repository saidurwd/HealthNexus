@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Countries</h3>
                    <a href="{{ route('admin.master-data.index') }}" class="btn btn-secondary float-right">Back to Master Data</a>
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
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center">No countries found.</td></tr>
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
