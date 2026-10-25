@extends('layouts.adminlte')

@section('page_title', 'Procedures')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Procedures</h3>
            <a href="{{ route('admin.ot.procedures.create') }}" class="btn btn-primary float-right">New Procedure</a>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Code</th><th>Name</th><th>Specialty</th><th>Category</th><th>Duration</th><th>Day Care</th></tr></thead>
                <tbody>
                    @forelse($procedures as $procedure)
                        <tr>
                            <td>{{ $procedure->code }}</td>
                            <td>{{ $procedure->name }}</td>
                            <td>{{ $procedure->specialty?->name ?? '—' }}</td>
                            <td>{{ $procedure->category?->name ?? '—' }}</td>
                            <td>{{ $procedure->default_duration_minutes }} min</td>
                            <td>{{ $procedure->day_care_eligible ? 'Yes' : 'No' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No procedures configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Add Procedure Category</h3></div>
        <form action="{{ route('admin.ot.procedure-categories.store') }}" method="post">
            @csrf
            <div class="card-body row">
                <div class="col-md-5"><input name="code" class="form-control" placeholder="Code" required></div>
                <div class="col-md-5"><input name="name" class="form-control" placeholder="Name" required></div>
                <div class="col-md-2"><button class="btn btn-primary">Save</button></div>
            </div>
        </form>
    </div>
@stop
