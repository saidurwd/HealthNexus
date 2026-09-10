@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('auth_body')
    <form action="{{ route('login') }}" method="post">
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

        <label for="password" class="visually-hidden">Password</label>

        <div class="input-group mb-3">
            <input type="password" name="password" id="password"
                class="form-control @error('password') is-invalid @enderror"
                placeholder="Password">

            <div class="input-group-text">
                <span class="bi bi-lock-fill"></span>
            </div>

            @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="row">
            <div class="col-7">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember"
                           id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember">
                        Remember me
                    </label>
                </div>
            </div>

            <div class="col-5">
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-box-arrow-in-right me-1"></i>
                        Next
                    </button>
                </div>
            </div>
        </div>
    </form>
@stop
