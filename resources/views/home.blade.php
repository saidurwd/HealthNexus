@extends('layouts.adminlte')

@section('page_content')
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $userCompanies->count() }}</h3>
                    <p>Companies</p>
                </div>
                <div class="icon">
                    <i class="bi bi-building"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $currentCompany ? $currentCompany->branches()->count() : 0 }}</h3>
                    <p>Branches</p>
                </div>
                <div class="icon">
                    <i class="bi bi-shop"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $currentCompany ? $currentCompany->departments()->count() : 0 }}</h3>
                    <p>Departments</p>
                </div>
                <div class="icon">
                    <i class="bi bi-diagram-3"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $currentCompany ? $currentCompany->users()->count() : 0 }}</h3>
                    <p>Users</p>
                </div>
                <div class="icon">
                    <i class="bi bi-people"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Current Context</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-info"><i class="bi bi-building"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Company</span>
                                    <span class="info-box-number">
                                        @if($currentCompany)
                                            {{ $currentCompany->name }} ({{ $currentCompany->code }})
                                        @else
                                            No company selected
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-success"><i class="bi bi-shop"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Branch</span>
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
                                    <span class="info-box-text">Department</span>
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
                    <h3 class="card-title">Your Companies</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Status</th>
                                <th>Access Level</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($userCompanies as $userCompany)
                                <tr class="{{ $currentCompany && $currentCompany->id === $userCompany->id ? 'table-active' : '' }}">
                                    <td>{{ $userCompany->name }}</td>
                                    <td>{{ $userCompany->code }}</td>
                                    <td>
                                        <span class="badge {{ $userCompany->is_active ? 'bg-success' : 'bg-danger' }}">
                                            {{ $userCompany->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            {{ $userCompany->access_level ?? 'N/A' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">You are not assigned to any companies.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop