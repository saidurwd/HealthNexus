@extends('layouts.adminlte')

@section('page_title', __('Quick-start cards'))

@section('page_content')
    <div class="card">
        <div class="card-body">
            <p class="text-muted">{{ __('One page per role, in Bangla and English. Print them and put them next to the screen.') }}</p>
            <div class="list-group">
                @foreach($roles as $key => $title)
                    <a class="list-group-item list-group-item-action" target="_blank" href="{{ route('admin.help.quick-start.show', $key) }}">{{ $title }}</a>
                @endforeach
            </div>
        </div>
    </div>
@stop
