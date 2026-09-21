@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Currencies</h3>
                    <a href="{{ route('admin.master-data.index') }}" class="btn btn-secondary float-right">Back to Master Data</a>
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
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center">No currencies found.</td></tr>
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
