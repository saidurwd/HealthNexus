@extends('layouts.adminlte')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Master Data</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-sm-6 col-12 mb-3">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>Countries</h3>
                                    <p>Manage countries</p>
                                </div>
                                <div class="small-box-footer">
                                    <a href="{{ route('admin.master-data.countries') }}" class="text-white">Manage <i class="bi bi-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 col-12 mb-3">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>States</h3>
                                    <p>Manage states</p>
                                </div>
                                <div class="small-box-footer">
                                    <a href="{{ route('admin.master-data.states', ['country' => \App\Models\Country::first()?->id ?? 0]) }}" class="text-white">Manage <i class="bi bi-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 col-12 mb-3">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>Currencies</h3>
                                    <p>Manage currencies</p>
                                </div>
                                <div class="small-box-footer">
                                    <a href="{{ route('admin.master-data.currencies') }}" class="text-white">Manage <i class="bi bi-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 col-12 mb-3">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3>ID Types</h3>
                                    <p>Manage identification types</p>
                                </div>
                                <div class="small-box-footer">
                                    <a href="{{ route('admin.master-data.identification-types') }}" class="text-white">Manage <i class="bi bi-arrow-right"></i></a>
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
