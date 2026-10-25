@extends('layouts.adminlte')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Master Data') }}</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-sm-6 col-12 mb-3">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ __('Countries') }}</h3>
                                    <p>{{ __('Manage countries') }}</p>
                                </div>
                                <div class="small-box-footer">
                                    <a href="{{ route('admin.master-data.countries') }}" class="text-white">{{ __('Manage') }} <i class="bi bi-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 col-12 mb-3">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>{{ __('States') }}</h3>
                                    <p>{{ __('Manage states') }}</p>
                                </div>
                                <div class="small-box-footer">
                                    <a href="{{ route('admin.master-data.states', ['country' => \App\Models\Country::first()?->id ?? 0]) }}" class="text-white">{{ __('Manage') }} <i class="bi bi-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 col-12 mb-3">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ __('Currencies') }}</h3>
                                    <p>{{ __('Manage currencies') }}</p>
                                </div>
                                <div class="small-box-footer">
                                    <a href="{{ route('admin.master-data.currencies') }}" class="text-white">{{ __('Manage') }} <i class="bi bi-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 col-12 mb-3">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3>{{ __('ID Types') }}</h3>
                                    <p>{{ __('Manage identification types') }}</p>
                                </div>
                                <div class="small-box-footer">
                                    <a href="{{ route('admin.master-data.identification-types') }}" class="text-white">{{ __('Manage') }} <i class="bi bi-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
