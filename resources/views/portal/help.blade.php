@extends('portal.layout')
@section('title', __('Help'))
@section('content')
<h1>{{ __('Help') }}</h1>
<div class="emerg"><strong>{{ __('Emergency') }}:</strong> {{ __('call :n or go to the nearest emergency department.', ['n' => $n]) }}</div>
<div class="card"><h2 style="margin-top:0">{{ __('What this portal is for') }}</h2><p>{{ __('Booking visits, reading released results, asking for refills, paying bills and writing to the hospital about things that are not urgent.') }}</p><h2>{{ __('Why a result is not shown') }}</h2><p>{{ __('Some results are kept until your doctor has looked at them so that they can explain them to you. Critical results are never shown by themselves: the doctor will call you.') }}</p><h2>{{ __('Your privacy') }}</h2><p>{{ __('Only you, and people you or the hospital have approved, can see your record. Every time a result or document is opened it is written down. You can see and end family access under Family.') }}</p></div>
@endsection
