<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>@yield('title', __('My health')) · {{ config('app.name') }}</title>
<style>
:root{--c:#0b6e4f;--bg:#f6f8f7;--ink:#17201c;--mute:#5d6b64;--line:#dfe6e2;--warn:#8a5a00;--bad:#a42424}
*{box-sizing:border-box}body{margin:0;font:16px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,"Noto Sans Bengali",sans-serif;color:var(--ink);background:var(--bg)}
a{color:var(--c)}header{background:var(--c);color:#fff}header a{color:#fff;text-decoration:none}
.bar{max-width:880px;margin:0 auto;padding:.6rem 1rem;display:flex;gap:.75rem;align-items:center;justify-content:space-between}
nav{max-width:880px;margin:0 auto;display:flex;flex-wrap:wrap;gap:.25rem;padding:0 .5rem .5rem}nav a{padding:.35rem .7rem;border-radius:.5rem;font-size:.95rem}nav a.on{background:rgba(255,255,255,.2)}
main{max-width:880px;margin:1rem auto;padding:0 1rem 3rem}h1{font-size:1.4rem;margin:.2rem 0 1rem}h2{font-size:1.1rem;margin:1.2rem 0 .5rem}
.card{background:#fff;border:1px solid var(--line);border-radius:.75rem;padding:1rem;margin:0 0 .9rem}.row{display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;justify-content:space-between}
.muted{color:var(--mute);font-size:.9rem}.tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.75rem;margin-bottom:1rem}.tile{background:#fff;border:1px solid var(--line);border-radius:.75rem;padding:.8rem}.tile b{display:block;font-size:1.5rem}
label{display:block;font-weight:600;margin:.6rem 0 .2rem}input,select,textarea{width:100%;padding:.6rem .7rem;border:1px solid #b9c5be;border-radius:.5rem;font:inherit;background:#fff}textarea{min-height:5.5rem}
input[type=checkbox],input[type=radio]{width:auto;margin-right:.4rem}.check{font-weight:400;display:flex;align-items:center;margin:.3rem 0}
button,.btn{display:inline-block;background:var(--c);color:#fff;border:0;border-radius:.5rem;padding:.6rem 1rem;font:inherit;cursor:pointer;text-decoration:none}.btn.alt,button.alt{background:#fff;color:var(--c);border:1px solid var(--c)}button.bad{background:var(--bad)}
.flash{padding:.7rem 1rem;border-radius:.5rem;margin-bottom:1rem}.ok{background:#e3f4ea;color:#12502f}.err{background:#fbe6e6;color:var(--bad)}.warn{background:#fff3d6;color:var(--warn)}
table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:.45rem .3rem;border-bottom:1px solid var(--line);vertical-align:top}th{font-size:.85rem;color:var(--mute)}
.pill{display:inline-block;padding:.1rem .55rem;border-radius:1rem;font-size:.82rem;background:#e8eeea}.pill.good{background:#dff3e6;color:#12502f}.pill.bad{background:#fbe6e6;color:var(--bad)}.pill.wait{background:#fff3d6;color:var(--warn)}
.emerg{background:#fff3d6;border:1px solid #e6c36a;border-radius:.6rem;padding:.6rem .9rem;margin-bottom:1rem;font-size:.95rem}.bubble{padding:.6rem .8rem;border-radius:.7rem;background:#eef2ef;white-space:pre-wrap;display:inline-block;max-width:92%}.bubble.me{background:#dff3e6}
@media print{header,nav,.noprint{display:none}body{background:#fff}.card{border:0}}
</style>
</head>
<body>
<header><div class="bar"><a href="{{ route('portal.home') }}"><strong>{{ config('app.name') }}</strong></a>
<span>@auth('portal')<form method="POST" action="{{ route('portal.logout') }}" style="display:inline">@csrf<button class="alt" style="padding:.25rem .7rem">{{ __('Sign out') }}</button></form> @endauth
<a href="{{ route('portal.language', app()->getLocale() === 'bn' ? 'en' : 'bn') }}">{{ app()->getLocale() === 'bn' ? 'English' : 'বাংলা' }}</a></span></div>
@auth('portal')<nav>@foreach([['portal.home', __('Home')], ['portal.appointments', __('Appointments')], ['portal.results', __('Results')], ['portal.prescriptions', __('Medicines')], ['portal.billing', __('Bills')], ['portal.threads', __('Messages')], ['portal.family', __('Family')], ['portal.settings', __('Settings')]] as [$r, $t])<a href="{{ route($r) }}" class="{{ request()->routeIs($r.'*') ? 'on' : '' }}">{{ $t }}</a>@endforeach</nav>@endauth</header>
<main>
@if(session('success'))<div class="flash ok" role="status">{{ session('success') }}</div>@endif
@if(session('error'))<div class="flash err" role="alert">{{ session('error') }}</div>@endif
@if($errors->any())<div class="flash err" role="alert">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
@yield('content')
</main>
</body>
</html>
