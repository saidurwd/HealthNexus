@extends('adminlte::master')

@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')

@php
    $dashboardUrl = View::getSection('dashboard_url')
        ?? config('adminlte.dashboard_url', 'home');

    $dashboardUrl = $layoutHelper->makeUrl($dashboardUrl);

    $errorCode = $errorCode ?? '500';
    $errorTheme = $errorTheme ?? 'danger';
@endphp

@section('title', $errorTitle ?? $errorCode)

@section('classes_body', 'bg-body-tertiary')

@section('adminlte_css')
    @stack('css')
    @yield('css')
@stop

@section('body')
    <main class="d-flex align-items-center min-vh-100 py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 text-center">

                    {{-- Error code or icon --}}
                    @hasSection('error_icon')
                        @yield('error_icon')
                    @else
                        <div class="display-1 fw-bold text-{{ $errorTheme }} lh-1 mb-3">
                            {{ $errorCode }}
                        </div>
                    @endif

                    {{-- Error title --}}
                    <h1 class="h3 mb-3">
                        @hasSection('error_title')
                            @yield('error_title')
                        @else
                            {{ $errorTitle ?? '' }}
                        @endif
                    </h1>

                    {{-- Error description --}}
                    <p class="text-secondary mb-4">
                        @hasSection('error_message')
                            @yield('error_message')
                        @else
                            {{ $errorMessage ?? '' }}
                        @endif
                    </p>

                    {{-- Reference id (guideline 19.2 rule 11): lets support find exactly what happened --}}
                    @if(app()->bound('request_id'))
                        <p class="small text-muted mb-4">
                            {{ __('Reference') }}: <code>{{ app('request_id') }}</code><br>
                            {{ __('Tell support this reference so they can find what happened.') }}
                        </p>
                    @endif

                    {{-- Extra content (a search form, a support card, ...) --}}
                    @yield('error_content')

                    {{-- Actions --}}
                    @hasSection('error_actions')
                        @yield('error_actions')
                    @elseif($dashboardUrl)
                        <a href="javascript:history.back()" class="btn btn-outline-secondary me-1">
                            <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i> {{ __('Go back') }}
                        </a>
                        <a href="{{ $dashboardUrl }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                            {{ __('adminlte::adminlte.back_to_dashboard') }}
                        </a>
                    @endif

                </div>
            </div>
        </div>
    </main>
@stop

@section('adminlte_js')
    @stack('js')
    @yield('js')
@stop
