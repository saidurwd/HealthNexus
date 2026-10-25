@extends('adminlte::page')

@section('title', config('adminlte.title', 'HealthNexus'))

@section('title', config('adminlte.title', 'HealthNexus'))

@section('content_top_nav_right')
    @include('components.nav-notifications-messages')
@stop

@section('content_top_nav_left')
    @php
        $organization = app(\App\Services\TenantContextResolver::class)->getOrganization();
        $branch = app(\App\Services\TenantContextResolver::class)->getBranch();
    @endphp
    @if($organization)
        <li class="nav-item d-none d-md-flex align-items-center">
            <span class="nav-link text-secondary small mb-0">
                <i class="bi bi-building me-1"></i>
                <span class="fw-semibold">{{ $organization->name }}</span>
                @if($branch)
                    <span class="mx-1">·</span>
                    <i class="bi bi-geo-alt me-1"></i>
                    {{ $branch->name }}
                @endif
            </span>
        </li>
    @endif
    {{-- @include('components.tenant-context-selector') --}}
    <li class="nav-item d-none d-md-flex align-items-center">
        <form method="GET" action="{{ route('admin.search') }}" class="mb-0">
            <input type="search" name="q" id="global-search" class="form-control form-control-sm" style="width:260px"
                   placeholder="{{ __('Search or scan… (Ctrl+K / F3)') }}" autocomplete="off">
        </form>
    </li>
@stop

@push('js')
<div class="modal fade" id="shortcut-help" tabindex="-1" aria-labelledby="shortcut-help-title" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="shortcut-help-title">{{ __('Keyboard shortcuts') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button></div>
        <div class="modal-body">
            <table class="table table-sm mb-0">
                <tr><td><kbd>Ctrl</kbd> + <kbd>K</kbd>, <kbd>F3</kbd>, <kbd>/</kbd></td><td>{{ __('Search patients, or scan a barcode') }}</td></tr>
                <tr><td><kbd>Enter</kbd></td><td>{{ __('Move to the next field (forms marked for fast entry)') }}</td></tr>
                <tr><td><kbd>?</kbd></td><td>{{ __('Show this list') }}</td></tr>
                <tr><td><kbd>Esc</kbd></td><td>{{ __('Close a window') }}</td></tr>
            </table>
        </div>
    </div></div>
</div>
<script>
    // Search from anywhere; a barcode scanner types the code and presses Enter, so it never needs a mouse click (rule 10).
    document.addEventListener('keydown', function (e) {
        var tag = (e.target.tagName || '').toLowerCase();
        var typing = tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable;
        var box = document.getElementById('global-search');

        if ((e.ctrlKey && e.key.toLowerCase() === 'k') || e.key === 'F3' || (!typing && e.key === '/')) {
            e.preventDefault();
            if (box) { box.focus(); box.select(); }
        } else if (!typing && e.key === '?') {
            e.preventDefault();
            var modal = document.getElementById('shortcut-help');
            if (modal && window.bootstrap) { window.bootstrap.Modal.getOrCreateInstance(modal).show(); }
        }
    });

    // Forms marked data-enter-next: Enter moves to the next field instead of submitting early (follows the paper form's tab order).
    document.addEventListener('keydown', function (e) {
        var form = e.target.form;
        if (e.key !== 'Enter' || !form || !form.hasAttribute('data-enter-next')) { return; }
        var tag = e.target.tagName.toLowerCase();
        if (tag === 'textarea' || tag === 'button' || e.target.type === 'submit') { return; }
        var fields = Array.prototype.filter.call(form.elements, function (f) { return !f.disabled && f.type !== 'hidden' && f.offsetParent !== null; });
        var next = fields[fields.indexOf(e.target) + 1];
        if (next && next.type !== 'submit') { e.preventDefault(); next.focus(); }
    });
</script>
@endpush

@section('footer')
    <strong>&copy; {{ date('Y') }} Health Nexus</strong>
    <span class="mx-2">|</span>
    <small>v{{ config('app.version', '1.0.0') }}</small>
    <span class="d-none d-md-inline">
        <span class="mx-2">|</span>
        <a href="https://github.com/Kilo-Org/kilocode" class="text-reset text-decoration-none" target="_blank">{{ __('Support') }}</a>
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
