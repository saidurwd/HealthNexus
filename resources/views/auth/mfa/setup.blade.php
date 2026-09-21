@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Setup Two-Factor Authentication') }}</div>

                <div class="card-body">
                    <p class="mb-4">{{ __('Scan the QR code with your authenticator app or enter the secret key manually.') }}</p>

                    <div class="text-center mb-4">
                        <img src="{{ $qrCode }}" alt="QR Code" width="200" height="200">
                    </div>

                    <div class="alert alert-info">
                        <strong>Secret Key:</strong> <code>{{ $secret }}</code>
                    </div>

                    <form method="POST" action="{{ route('mfa.setup.store') }}">
                        @csrf

                        <input type="hidden" name="secret" value="{{ $secret }}">

                        <div class="mb-3">
                            <label for="one_time_password" class="form-label">{{ __('Authentication Code') }}</label>
                            <input type="text" class="form-control @error('one_time_password') is-invalid @enderror" id="one_time_password" name="one_time_password" autofocus autocomplete="off" maxlength="6" pattern="[0-9]{6}" inputmode="numeric">

                            @error('one_time_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100">{{ __('Enable MFA') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
