@extends('layouts.adminlte')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create Country</h3>
                    <a href="{{ route('admin.master-data.countries') }}" class="btn btn-secondary float-right">Back</a>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.master-data.countries.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="code" class="form-label">Code</label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" required maxlength="10">
                            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="currency_code" class="form-label">Currency Code</label>
                            <input type="text" class="form-control @error('currency_code') is-invalid @enderror" id="currency_code" name="currency_code" value="{{ old('currency_code') }}" maxlength="3">
                            @error('currency_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="currency_symbol" class="form-label">Currency Symbol</label>
                            <input type="text" class="form-control @error('currency_symbol') is-invalid @enderror" id="currency_symbol" name="currency_symbol" value="{{ old('currency_symbol') }}" maxlength="10">
                            @error('currency_symbol')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="phone_code" class="form-label">Phone Code</label>
                            <input type="text" class="form-control @error('phone_code') is-invalid @enderror" id="phone_code" name="phone_code" value="{{ old('phone_code') }}" maxlength="20">
                            @error('phone_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="timezone" class="form-label">Timezone</label>
                            <input type="text" class="form-control @error('timezone') is-invalid @enderror" id="timezone" name="timezone" value="{{ old('timezone') }}" maxlength="100">
                            @error('timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="date_format" class="form-label">Date Format</label>
                            <input type="text" class="form-control @error('date_format') is-invalid @enderror" id="date_format" name="date_format" value="{{ old('date_format') ?? 'd/m/Y' }}" maxlength="50">
                            @error('date_format')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="time_format" class="form-label">Time Format</label>
                            <input type="text" class="form-control @error('time_format') is-invalid @enderror" id="time_format" name="time_format" value="{{ old('time_format') ?? 'H:i' }}" maxlength="50">
                            @error('time_format')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
