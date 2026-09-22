@extends('adminlte::page')

@section('title', config('adminlte.title', 'HealthNexus'))

@section('title', config('adminlte.title', 'HealthNexus'))

@section('content_top_nav_right')
    @include('components.nav-notifications-messages')
@stop

@section('content_top_nav_left')
    @php
        $company = app(\App\Services\TenantContextResolver::class)->getCompany();
        $branch = app(\App\Services\TenantContextResolver::class)->getBranch();
    @endphp
    @if($company)
        <li class="nav-item d-none d-md-flex align-items-center">
            <span class="nav-link text-secondary small mb-0">
                <i class="bi bi-building me-1"></i>
                <span class="fw-semibold">{{ $company->name }}</span>
                @if($branch)
                    <span class="mx-1">·</span>
                    <i class="bi bi-geo-alt me-1"></i>
                    {{ $branch->name }}
                @endif
            </span>
        </li>
    @endif
    {{-- @include('components.tenant-context-selector') --}}
@stop

@section('footer')
    <strong>&copy; {{ date('Y') }} Health Nexus</strong>
    <span class="mx-2">|</span>
    <small>v{{ config('app.version', '1.0.0') }}</small>
    <span class="d-none d-md-inline">
        <span class="mx-2">|</span>
        <a href="https://github.com/Kilo-Org/kilocode" class="text-reset text-decoration-none" target="_blank">Support</a>
    </span>
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
