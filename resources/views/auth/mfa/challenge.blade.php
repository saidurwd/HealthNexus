@extends('layouts.adminlte')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">{{ __('Two-Factor Authentication') }}</div>

                <div class="card-body">
                    <p class="mb-4">{{ __('Please enter the 6-digit code from your authenticator app.') }}</p>

                    <form method="POST" action="{{ route('mfa.verify') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="one_time_password" class="form-label">{{ __('Authentication Code') }}</label>
                            <input type="text" class="form-control @error('one_time_password') is-invalid @enderror" id="one_time_password" name="one_time_password" autofocus autocomplete="off" maxlength="6" pattern="[0-9]{6}" inputmode="numeric">

                            @error('one_time_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100">{{ __('Verify') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
