@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('auth_header', 'Reset Password')

@section('auth_body')
    <form action="{{ route('password.update') }}" method="post">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        <label for="password" class="visually-hidden">Password</label>

        <div class="input-group mb-3">
            <input type="password" name="password" id="password"
                class="form-control @error('password') is-invalid @enderror"
                placeholder="New Password" required>

            <div class="input-group-text">
                <span class="bi bi-lock-fill"></span>
            </div>

            @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <label for="password_confirmation" class="visually-hidden">Confirm Password</label>

        <div class="input-group mb-3">
            <input type="password" name="password_confirmation" id="password_confirmation"
                class="form-control"
                placeholder="Confirm New Password" required>

            <div class="input-group-text">
                <span class="bi bi-lock-fill"></span>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        Reset Password
                    </button>
                </div>
            </div>
        </div>
    </form>
@stop
