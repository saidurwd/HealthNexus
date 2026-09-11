@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('auth_header', 'Reset Password')

@section('auth_body')
    <form action="{{ route('password.update') }}" method="post">
        @csrf

        <label for="email" class="visually-hidden">Email</label>

        <div class="input-group mb-3">
            <input type="email" name="email" id="email"
                class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email') }}" placeholder="Email" autofocus>

            <div class="input-group-text">
                <span class="bi bi-envelope"></span>
            </div>

            @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="row">
            <div class="col-12">
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        Send Password Reset Link
                    </button>
                </div>
            </div>
        </div>
    </form>

    <p class="mt-3 text-center">
        <a href="{{ route('login') }}">Back to login</a>
    </p>
@stop
