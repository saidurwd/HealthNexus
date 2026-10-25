@extends('layouts.adminlte')

@section('page_content')
    {{-- Reusable dashboard widgets — see App\Services\Dashboard\DashboardWidgetRegistry. Any
         module can register a widget here without touching this view or HomeController. --}}
    <div class="row">
        @forelse($widgets as $widget)
            <div class="col-lg-3 col-6">
                <div class="small-box bg-{{ $widget->color }}" data-widget-key="{{ $widget->key }}" data-refresh-interval="{{ $widget->refreshIntervalSeconds }}">
                    <div class="inner">
                        <h3>{{ $widget->resolveValue() }}</h3>
                        <p>{{ $widget->title }}</p>
                    </div>
                    <div class="icon">
                        <i class="{{ $widget->icon }}"></i>
                    </div>
                    @if($widget->url())
                        <a href="{{ $widget->url() }}" class="small-box-footer">
                            {{ __('More info') }} <i class="bi bi-arrow-right-circle"></i>
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-12">
                <p class="text-muted">{{ __('No dashboard widgets are available for your role.') }}</p>
            </div>
        @endforelse
    </div>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $currentOrganization ? $currentOrganization->departments()->count() : 0 }}</h3>
                    <p>{{ __('Departments (current organization)') }}</p>
                </div>
                <div class="icon">
                    <i class="bi bi-diagram-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Current Context') }}</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-info"><i class="bi bi-building"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">{{ __('Organization') }}</span>
                                    <span class="info-box-number">
                                        @if($currentOrganization)
                                            {{ $currentOrganization->name }} ({{ $currentOrganization->code }})
                                        @else
                                            No organization selected
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-success"><i class="bi bi-shop"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">{{ __('Branch') }}</span>
                                    <span class="info-box-number">
                                        @if($currentBranch)
                                            {{ $currentBranch->name }} ({{ $currentBranch->code }})
                                        @else
                                            No branch selected
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-warning"><i class="bi bi-diagram-3"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">{{ __('Department') }}</span>
                                    <span class="info-box-number">
                                        @if($currentDepartment)
                                            {{ $currentDepartment->name }} ({{ $currentDepartment->code }})
                                        @else
                                            No department selected
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Your Organizations') }}</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Code') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Access Level') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($userOrganizations as $userOrganization)
                                <tr class="{{ $currentOrganization && $currentOrganization->id === $userOrganization->id ? 'table-active' : '' }}">
                                    <td>{{ $userOrganization->name }}</td>
                                    <td>{{ $userOrganization->code }}</td>
                                    <td>
                                        <span class="badge {{ $userOrganization->is_active ? 'bg-success' : 'bg-danger' }}">
                                            {{ $userOrganization->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            {{ $userOrganization->access_level ?? 'N/A' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">{{ __('You are not assigned to any organizations.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop