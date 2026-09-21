@extends('adminlte::page')

@section('title', config('adminlte.title', 'HealthNexus'))

@section('content_top_nav_left')
    @include('components.tenant-context-selector')
@stop

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">@yield('page_title', 'Dashboard')</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    @auth
                        {!! app(\App\Services\Breadcrumbs::class)->render() !!}
                    @endauth
                </ol>
            </div>
        </div>
    </div>
@stop

@section('content')
    @yield('page_content')
@stop
