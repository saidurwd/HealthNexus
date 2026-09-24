@extends('layouts.adminlte')

@section('page_title', 'Edit Bed')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Edit Bed</h3></div>
        <form action="{{ route('admin.ipd.beds.update', $bed) }}" method="post">
            @csrf
            @method('PUT')
            @include('admin.ipd.beds._form')
            <div class="card-footer">
                <a href="{{ route('admin.ipd.beds.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Bed</button>
            </div>
        </form>
        @can('unblock', $bed)
            @php($activeBlock = $bed->blocks()->where('status', 'active')->first())
            @if($activeBlock)
                <div class="card-footer">
                    <form method="POST" action="{{ route('admin.ipd.bed-blocks.unblock', $activeBlock) }}">
                        @csrf
                        <button type="submit" class="btn btn-success">Unblock Bed</button>
                    </form>
                </div>
            @endif
        @endcan
    </div>
@stop
