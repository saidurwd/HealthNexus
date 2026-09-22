@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('auth_header', 'Verify Your Email')

@section('auth_body')
    <p class="text-center">
        Thanks for signing up! Before continuing, please check your email for a verification link.
    </p>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @error('email')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <form action="{{ route('verification.send') }}" method="post">
        @csrf
        <div class="d-grid">
            <button type="submit" class="btn btn-primary">Resend Verification Email</button>
        </div>
    </form>

    <form action="{{ route('logout') }}" method="post" class="mt-2">
        @csrf
        <div class="d-grid">
            <button type="submit" class="btn btn-link">Log Out</button>
        </div>
    </form>
@stop
