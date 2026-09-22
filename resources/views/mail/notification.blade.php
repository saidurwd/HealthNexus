@component('mail::message')
# {{ $title }}

{{ $message }}

@isset($data)
@component('mail::panel')
@foreach ($data as $key => $value)
- **{{ ucfirst($key) }}**: {{ $value }}
@endforeach
@endcomponent
@endisset

Thanks,<br>
{{ config('app.name') }}
@endcomponent
