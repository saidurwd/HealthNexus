@extends('layouts.adminlte')

@section('page_title', 'New ICU Procedure')

@section('page_content')
    <form method="POST" action="{{ route('admin.icu.procedures.store') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <label>Code</label>
                    <input type="text" name="code" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" class="form-control">
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </div>
    </form>
@stop
