@extends('layouts.adminlte')

@section('page_title', 'Edit Medication')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Edit Medication</h3></div>
        <form action="{{ route('admin.pharmacy.medications.update', $medication) }}" method="post">
            @csrf
            @method('PUT')
            @include('admin.pharmacy.medications._form')
            <div class="card-footer">
                <a href="{{ route('admin.pharmacy.medications.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Medication</button>
                @can('delete', $medication)
                    <button type="submit" form="deactivate-form" class="btn btn-danger float-end">Deactivate</button>
                @endcan
            </div>
        </form>
        @can('delete', $medication)
            <form id="deactivate-form" action="{{ route('admin.pharmacy.medications.destroy', $medication) }}" method="post" onsubmit="return confirm('Deactivate this medication?')">
                @csrf
                @method('DELETE')
            </form>
        @endcan
    </div>
@stop
