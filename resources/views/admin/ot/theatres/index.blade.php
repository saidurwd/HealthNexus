@extends('layouts.adminlte')

@section('page_title', 'Theatres')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Theatres</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Code</th><th>Name</th><th>Complex</th><th>Capabilities</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($theatres as $theatre)
                        <tr>
                            <td>{{ $theatre->theatre_code }}</td>
                            <td>{{ $theatre->name }}</td>
                            <td>{{ $theatre->complex?->name ?? '—' }}</td>
                            <td>{{ $theatre->capabilities->pluck('capability')->implode(', ') }}</td>
                            <td><span class="badge bg-info">{{ ucfirst($theatre->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No theatres configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('ot.theatre.manage')
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Add OT Complex</h3></div>
                <form action="{{ route('admin.ot.complexes.store') }}" method="post">
                    @csrf
                    <div class="card-body">
                        <input name="code" class="form-control mb-2" placeholder="Code" required>
                        <input name="name" class="form-control mb-2" placeholder="Name" required>
                    </div>
                    <div class="card-footer"><button class="btn btn-primary">Save</button></div>
                </form>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Add Theatre</h3></div>
                <form action="{{ route('admin.ot.theatres.store') }}" method="post">
                    @csrf
                    <div class="card-body">
                        <select name="complex_id" class="form-control mb-2">
                            @foreach($complexes as $complex)<option value="{{ $complex->id }}">{{ $complex->name }}</option>@endforeach
                        </select>
                        <input name="theatre_code" class="form-control mb-2" placeholder="Theatre code" required>
                        <input name="name" class="form-control mb-2" placeholder="Name" required>
                    </div>
                    <div class="card-footer"><button class="btn btn-primary">Save</button></div>
                </form>
            </div>
        </div>
    </div>
    @endcan
@stop
