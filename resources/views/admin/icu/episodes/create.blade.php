@extends('layouts.adminlte')

@section('page_title', 'Admit to ICU')

@section('page_content')
    <form method="POST" action="{{ route('admin.icu.episodes.store') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <label>Admission Request ID</label>
                    <input type="number" name="admission_request_id" class="form-control" value="{{ $icuRequest?->id }}" required>
                </div>
                <div class="form-group">
                    <label>ICU Bed ID</label>
                    <input type="number" name="bed_id" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Attending Provider ID</label>
                    <input type="number" name="attending_provider_id" class="form-control">
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Admit Patient</button>
            </div>
        </div>
    </form>
@stop
