@extends('layouts.adminlte')

@section('page_title', __('Branding, e-mail and contact'))

@section('page_content')
    @php $tabs = ['general' => ['bi-building', __('General')], 'branding' => ['bi-image', __('Logo and icon')], 'contact' => ['bi-telephone', __('Footer and support')], 'email' => ['bi-envelope-at', __('E-mail')]]; $canEdit = auth()->user()->can('settings.update'); @endphp

    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <ul class="nav nav-tabs mb-3">
        @foreach($tabs as $key => [$icon, $label])
            <li class="nav-item"><a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ route('admin.settings.system', ['tab' => $key]) }}"><i class="bi {{ $icon }}"></i> {{ $label }}</a></li>
        @endforeach
    </ul>

    @if($tab === 'general')
        <form method="POST" action="{{ route('admin.settings.system.general') }}" class="card">@csrf @method('PUT')
            <div class="card-header"><h3 class="card-title">{{ __('Names and address') }}</h3></div>
            <div class="card-body row g-3">
                <div class="col-md-6"><label class="form-label">{{ __('System name') }} *</label><input name="app_name" class="form-control" maxlength="60" required value="{{ old('app_name', $v['app_name']) }}"><div class="form-text">{{ __('Shown in the browser tab, the sidebar, sign-in page and e-mails.') }}</div></div>
                <div class="col-md-6"><label class="form-label">{{ __('Hospital name') }}</label><input name="hospital_name" class="form-control" maxlength="120" value="{{ old('hospital_name', $v['hospital_name']) }}"><div class="form-text">{{ __('Printed on letterheads and documents.') }}</div></div>
                <div class="col-md-4"><label class="form-label">{{ __('Phone') }}</label><input name="hospital_phone" class="form-control" maxlength="40" value="{{ old('hospital_phone', $v['hospital_phone']) }}"></div>
                <div class="col-md-4"><label class="form-label">{{ __('E-mail') }}</label><input type="email" name="hospital_email" class="form-control" maxlength="120" value="{{ old('hospital_email', $v['hospital_email']) }}"></div>
                <div class="col-md-4"><label class="form-label">{{ __('Website') }}</label><input type="url" name="hospital_website" class="form-control" maxlength="190" placeholder="https://" value="{{ old('hospital_website', $v['hospital_website']) }}"></div>
                <div class="col-12"><label class="form-label">{{ __('Address') }}</label><input name="hospital_address" class="form-control" maxlength="255" value="{{ old('hospital_address', $v['hospital_address']) }}"></div>
            </div>
            @if($canEdit)<div class="card-footer text-end"><button class="btn btn-primary">{{ __('Save') }}</button></div>@endif
        </form>
    @elseif($tab === 'branding')
        <div class="row g-3">
            <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Logo') }}</h3></div>
                <div class="card-body">
                    <div class="border rounded p-3 mb-3 text-center bg-body-tertiary" style="min-height:110px">@if($logo)<img src="{{ $logo }}" alt="" style="max-height:80px;max-width:100%">@else<span class="text-muted">{{ __('The standard logo is in use.') }}</span>@endif</div>
                    <form method="POST" action="{{ route('admin.settings.system.branding') }}" enctype="multipart/form-data">@csrf
                        <input type="file" name="logo" accept=".png,.jpg,.jpeg,.webp" class="form-control" @disabled(! $canEdit)>
                        <div class="form-text">{{ __('PNG, JPG or WebP, at least 64 × 64 pixels, up to 1 MB. A square or wide picture on a clear background works best. Used in the sidebar, sign-in page and printouts.') }}</div>
                        @if($canEdit)<button class="btn btn-primary mt-3">{{ __('Upload logo') }}</button>@endif
                    </form>
                    @if($logo && $canEdit)<form method="POST" action="{{ route('admin.settings.system.branding.remove', 'logo') }}" class="d-inline" onsubmit="return confirm('{{ __('Go back to the standard logo?') }}')">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm mt-3">{{ __('Remove') }}</button></form>@endif
                </div></div></div>
            <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Browser icon (favicon)') }}</h3></div>
                <div class="card-body">
                    <div class="border rounded p-3 mb-3 text-center bg-body-tertiary" style="min-height:110px">@if($favicon)<img src="{{ $favicon }}" alt="" style="height:48px;width:48px;object-fit:contain">@else<span class="text-muted">{{ __('The standard icon is in use.') }}</span>@endif</div>
                    <form method="POST" action="{{ route('admin.settings.system.branding') }}" enctype="multipart/form-data">@csrf
                        <input type="file" name="favicon" accept=".png,.ico" class="form-control" @disabled(! $canEdit)>
                        <div class="form-text">{{ __('A square PNG (32 × 32 or 64 × 64) or an ICO file, up to 256 KB.') }}</div>
                        @if($canEdit)<button class="btn btn-primary mt-3">{{ __('Upload icon') }}</button>@endif
                    </form>
                    @if($favicon && $canEdit)<form method="POST" action="{{ route('admin.settings.system.branding.remove', 'favicon') }}" class="d-inline" onsubmit="return confirm('{{ __('Go back to the standard icon?') }}')">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm mt-3">{{ __('Remove') }}</button></form>@endif
                </div></div></div>
        </div>
    @elseif($tab === 'contact')
        <form method="POST" action="{{ route('admin.settings.system.contact') }}" class="card">@csrf @method('PUT')
            <div class="card-header"><h3 class="card-title">{{ __('Footer and support') }}</h3></div>
            <div class="card-body row g-3">
                <div class="col-12"><label class="form-label">{{ __('Footer line') }}</label><input name="footer_text" class="form-control" maxlength="160" value="{{ old('footer_text', $v['footer_text']) }}"><div class="form-text">{{ __('Shown at the bottom of every screen, for example the hospital’s registration or licence line.') }}</div></div>
                <div class="col-md-4"><label class="form-label">{{ __('Support e-mail') }}</label><input type="email" name="support_email" class="form-control" maxlength="120" value="{{ old('support_email', $v['support_email']) }}"></div>
                <div class="col-md-4"><label class="form-label">{{ __('Support phone') }}</label><input name="support_phone" class="form-control" maxlength="40" value="{{ old('support_phone', $v['support_phone']) }}"></div>
                <div class="col-md-4"><label class="form-label">{{ __('Support page') }}</label><input type="url" name="support_url" class="form-control" maxlength="190" placeholder="https://" value="{{ old('support_url', $v['support_url']) }}"></div>
            </div>
            @if($canEdit)<div class="card-footer text-end"><button class="btn btn-primary">{{ __('Save') }}</button></div>@endif
        </form>
    @else
        <form method="POST" action="{{ route('admin.settings.system.email') }}" class="card mb-3">@csrf @method('PUT')
            <div class="card-header"><h3 class="card-title">{{ __('Outgoing e-mail') }}</h3></div>
            <div class="card-body row g-3">
                <div class="col-md-4"><label class="form-label">{{ __('Send mail with') }}</label>
                    <select name="mailer" class="form-select"><option value="smtp" @selected(old('mailer', $v['mailer']) === 'smtp')>{{ __('An SMTP mail server') }}</option><option value="log" @selected(old('mailer', $v['mailer']) === 'log')>{{ __('Nowhere — write to the log only (testing)') }}</option></select></div>
                <div class="col-md-5"><label class="form-label">{{ __('Server (host)') }}</label><input name="mail_host" class="form-control" maxlength="190" placeholder="smtp.example.com" value="{{ old('mail_host', $v['mail_host']) }}"></div>
                <div class="col-md-3"><label class="form-label">{{ __('Port') }}</label><input type="number" name="mail_port" class="form-control" min="1" max="65535" value="{{ old('mail_port', $v['mail_port']) }}"></div>
                <div class="col-md-4"><label class="form-label">{{ __('Security') }}</label>
                    <select name="mail_encryption" class="form-select">@foreach(['tls' => 'TLS (STARTTLS, usually port 587)', 'ssl' => 'SSL (usually port 465)', 'none' => __('None')] as $k => $l)<option value="{{ $k }}" @selected(old('mail_encryption', $v['mail_encryption']) === $k)>{{ $l }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">{{ __('User name') }}</label><input name="mail_username" class="form-control" maxlength="190" autocomplete="off" value="{{ old('mail_username', $v['mail_username']) }}"></div>
                <div class="col-md-4"><label class="form-label">{{ __('Password') }}</label><input type="password" name="mail_password" class="form-control" maxlength="190" autocomplete="new-password" placeholder="{{ $passwordSet ? '••••••••' : '' }}">
                    <div class="form-text">{{ $passwordSet ? __('A password is saved. Leave empty to keep it.') : __('No password is saved.') }} {{ __('It is stored encrypted and is never shown again.') }}</div>
                    @if($passwordSet)<div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="clear_password" value="1" id="clear_password"><label class="form-check-label" for="clear_password">{{ __('Remove the saved password') }}</label></div>@endif</div>
                <div class="col-md-6"><label class="form-label">{{ __('Send from (address)') }} *</label><input type="email" name="mail_from_address" class="form-control" maxlength="190" required value="{{ old('mail_from_address', $v['mail_from_address']) }}"></div>
                <div class="col-md-6"><label class="form-label">{{ __('Send from (name)') }} *</label><input name="mail_from_name" class="form-control" maxlength="80" required value="{{ old('mail_from_name', $v['mail_from_name']) }}"></div>
            </div>
            @if($canEdit)<div class="card-footer text-end"><button class="btn btn-primary">{{ __('Save') }}</button></div>@endif
        </form>
        @if($canEdit)
            <form method="POST" action="{{ route('admin.settings.system.email.test') }}" class="card">@csrf
                <div class="card-header"><h3 class="card-title">{{ __('Check that it works') }}</h3></div>
                <div class="card-body"><div class="row g-2 align-items-end"><div class="col-md-6"><label class="form-label">{{ __('Send a test message to') }}</label><input type="email" name="test_to" class="form-control" required value="{{ old('test_to', auth()->user()->email) }}"></div><div class="col-md-3"><button class="btn btn-outline-primary">{{ __('Send test') }}</button></div></div>
                    <div class="form-text mt-2">{{ __('Save the account first; the test uses what is saved.') }}</div></div>
            </form>
        @endif
    @endif
@stop
