@extends('layouts.adminlte')

@section('page_title', 'New Theatre')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">New Theatre</h3></div>
        <form action="{{ route('admin.ot.theatres.store') }}" method="post">
            @csrf
            <div class="card-body row">
                <div class="col-md-4 mb-3">
                    <label>OT Complex</label>
                    <select name="complex_id" class="form-control">
                        <option value="">None</option>
                        @foreach($complexes as $complex)<option value="{{ $complex->id }}">{{ $complex->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3"><label>Theatre Code</label><input name="theatre_code" class="form-control" required></div>
                <div class="col-md-4 mb-3"><label>Name</label><input name="name" class="form-control" required></div>
                <div class="col-md-4 mb-3"><label>Capacity</label><input type="number" name="capacity" class="form-control" value="1"></div>
                <div class="col-md-4 mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="emergency_capable" value="1" class="form-check-input" id="emergency_capable">
                        <label class="form-check-label" for="emergency_capable">Emergency Capable</label>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.ot.theatres.index') }}" class="btn btn-secondary">Cancel</a>
                <button class="btn btn-primary">Create</button>
            </div>
        </form>
    </div>
@stop
