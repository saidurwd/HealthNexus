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
    @php $brand = app(\App\Services\Branding\BrandingService::class); @endphp
    <strong>&copy; {{ date('Y') }} {{ config('app.name') }}</strong>
    @if($line = $brand->get('brand.footer_text'))<span class="mx-2">|</span><small>{{ $line }}</small>@endif
    <span class="mx-2">|</span>
    <small>v{{ config('app.version', '1.0.0') }}</small>
    @php $supportMail = $brand->get('brand.support_email'); $supportPhone = $brand->get('brand.support_phone'); $supportUrl = $brand->get('brand.support_url'); @endphp
    @if($supportMail || $supportPhone || $supportUrl)
        <span class="d-none d-md-inline">
            <span class="mx-2">|</span>
            {{ __('Support') }}:
            @if($supportUrl)<a href="{{ $supportUrl }}" class="text-reset" target="_blank" rel="noopener">{{ parse_url($supportUrl, PHP_URL_HOST) ?: $supportUrl }}</a>@endif
            @if($supportMail)<a href="mailto:{{ $supportMail }}" class="text-reset">{{ $supportMail }}</a>@endif
            @if($supportPhone)<span>{{ $supportPhone }}</span>@endif
        </span>
    @endif
@stop

@push('css')
    <link rel="stylesheet" href="{{ asset('css/print.css') }}" media="all">
    <style>
        .page-head { padding: .25rem .75rem .5rem; }
        .page-head .breadcrumb { background: transparent; padding: 0; margin: 0; flex-wrap: wrap; }
        .page-head .breadcrumb-item + .breadcrumb-item::before { content: "›"; }
        .page-head .breadcrumb a { text-decoration: none; }
        .page-head-title { letter-spacing: -.01em; }
        .brand-link .brand-logo-only { max-height: 44px; max-width: 100%; width: auto; height: auto; float: none; margin: 0 auto; display: block; opacity: 1; box-shadow: none; }
        .sidebar-collapse .brand-link .brand-logo-only { max-width: 36px; }
        #page-actions > #auto-print { order: 99; } #page-actions > #auto-export { order: 98; } /* Print always sits last, at the far right */
    </style>
@endpush

@section('content_header')
    @if(app(\App\Services\Ops\TrainingMode::class)->active())
        <div class="alert alert-warning text-center fw-bold mb-2 py-2" role="status" data-training-banner>
            <i class="bi bi-mortarboard-fill" aria-hidden="true"></i>
            {{ __('TRAINING — practice only. Nothing here is real, and nothing is sent to real people.') }}
        </div>
    @endif
    @php
        $routeName = (string) request()->route()?->getName();
        $isDashboard = $routeName === 'home' || $routeName === 'admin.today' || str_ends_with($routeName, '.dashboard') || str_contains($routeName, '.dashboards.');
        $printable = ! $isDashboard && request()->isMethod('GET') && ! str_contains((string) request()->route()?->getName(), 'create') && ! str_contains((string) request()->route()?->getName(), 'edit');
        $headTitle = trim((string) $__env->yieldContent('page_title', 'Dashboard'));
    @endphp
    <div class="container-fluid page-head d-print-none">
        @auth
            <nav aria-label="{{ __('Breadcrumb') }}"><ol class="breadcrumb small mb-1 justify-content-end">{!! app(\App\Services\Breadcrumbs::class)->render($headTitle) !!}</ol></nav>
        @endauth
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h1 class="page-head-title h3 fw-semibold mb-0" id="page-title">{{ $headTitle }}</h1>
            <div id="page-actions" class="d-flex flex-wrap align-items-center justify-content-end gap-2 ms-auto">
                @yield('page_actions')
                @if($printable && str_starts_with((string) request()->route()?->getName(), 'admin.') && ! \Illuminate\Support\Str::is((array) config('export.except', []), (string) request()->route()?->getName()))
                    <div class="dropdown" id="auto-export" style="display:none">
                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-download"></i> {{ __('Export') }}</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" data-export="xlsx"><i class="bi bi-file-earmark-excel text-success me-1"></i> {{ __('Excel (.xlsx)') }}</a></li>
                            <li><a class="dropdown-item" href="#" data-export="pdf"><i class="bi bi-file-earmark-pdf text-danger me-1"></i> {{ __('PDF') }}</a></li>
                        </ul>
                    </div>
                @endif
                @if($printable)
                    <button type="button" id="auto-print" class="btn btn-sm btn-outline-secondary" onclick="window.print()" title="{{ __('Print this page') }}"><i class="bi bi-printer"></i> {{ __('Print') }}</button>
                @endif
            </div>
        </div>
    </div>
@stop

@section('content')
    @php
        $printOrg = app(\App\Services\TenantContextResolver::class)->getOrganization();
        $printBranch = app(\App\Services\TenantContextResolver::class)->getBranch();
        $printWhen = now()->format('d M Y, H:i');
        $printUser = auth()->user()?->name;
    @endphp
    <div class="print-only print-letterhead">
        @php $printLogo = $printOrg?->logo ? (\Illuminate\Support\Str::startsWith($printOrg->logo, ['http', '/']) ? $printOrg->logo : asset('storage/'.$printOrg->logo)) : app(\App\Services\Branding\BrandingService::class)->assetUrl('logo'); @endphp
        @if($printLogo)<img class="logo" src="{{ $printLogo }}" alt="">@endif
        <div class="org">{{ $printOrg?->name ?? config('app.name') }}</div>
        <div class="meta">{{ collect([$printBranch?->name, $printOrg?->address, $printOrg?->phone, $printOrg?->email])->filter()->implode(' · ') }}</div>
        <div class="title">@yield('page_title', 'Dashboard')</div>
    </div>
    <div class="print-only print-footer"><span>{{ __('Printed by :name on :when', ['name' => $printUser ?? '—', 'when' => $printWhen]) }}</span><span>{{ __('Confidential — patient and hospital information') }}</span></div>
    @yield('page_content')
@stop

@push('js')
<script>
    // A screen that already carries its own print button (A4 cards, certificates) keeps that one only.
    document.addEventListener('DOMContentLoaded', function () {
        // A heading in the page that only repeats the page title is hidden, so the title shows once.
        var flat = function (t) { return (t || '').toLowerCase().replace(/[^\p{L}\p{N}]+/gu, ''); };
        var title = document.getElementById('page-title');
        if (title) {
            var want = flat(title.textContent);
            var main = document.querySelector('.app-content');
            if (main && want) {
                main.querySelectorAll('h1,h2,h3,h4,h5,h6,.card-title').forEach(function (h) {
                    if (flat(h.textContent) !== want) { return; }
                    h.classList.add('d-none');
                    var head = h.closest('.card-header');
                    if (head && head.textContent.trim() === '' && !head.querySelector('a,button,form,input,select')) { head.classList.add('d-none'); }
                });
            }
        }
        // A row at the top of the page that holds only buttons moves up to the right of the title.
        var bar = document.getElementById('page-actions');
        var wrap = document.querySelector('.app-content > .container-fluid') || document.querySelector('.app-content');
        // Many pages wrap everything in their own .container-fluid; look inside it.
        while (wrap && wrap.children.length && wrap.firstElementChild.matches('.container-fluid,.container')) { wrap = wrap.firstElementChild; }
        if (bar && wrap) {
            Array.prototype.slice.call(wrap.children, 0, 3).some(function (el) {
                if (el.matches('script,style,.alert,.card,.row,table,form[method="GET" i]') || el.querySelector('.btn') === null) { return false; }
                var probe = el.cloneNode(true);
                probe.querySelectorAll('.btn,.badge,.d-none,script,style').forEach(function (n) { n.remove(); });
                if (probe.textContent.trim() !== '' || probe.querySelector('input:not([type=hidden]),select,textarea,table,.card')) { return false; }
                el.className = (el.className || '').split(/\s+/).filter(function (c) { return !/^(m[tbsex]?-|justify-|float-|w-|col|d-print)/.test(c); }).join(' ');
                el.classList.add('d-flex', 'flex-wrap', 'align-items-center', 'gap-2');
                bar.insertBefore(el, bar.firstChild);
                return true;
            });
        }
        // Export is offered when the page shows a table with rows; the link is this same address with ?export=.
        var exp = document.getElementById('auto-export');
        if (exp && document.querySelector('.app-content table tbody tr')) {
            exp.style.display = '';
            exp.querySelectorAll('[data-export]').forEach(function (a) {
                a.addEventListener('click', function (e) {
                    e.preventDefault();
                    var u = new URL(window.location.href);
                    u.searchParams.set('export', a.getAttribute('data-export'));
                    window.location.href = u.toString();
                });
            });
        }
        // A dashboard is read on screen: no Print or Export in its header.
        if (document.querySelector('[data-dashboard]')) {
            ['auto-print', 'auto-export'].forEach(function (id) { var b = document.getElementById(id); if (b) { b.remove(); } });
        }
        var auto = document.getElementById('auto-print');
        if (auto && document.querySelector('[onclick*="window.print"]:not(#auto-print)')) { auto.remove(); }
    });
</script>
@endpush
