<!doctype html>
<html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"><title>{{ $patient->enterprise_patient_no }}</title>
<style>
    @page { size: 200mm 28mm; margin: 0; }
    * { box-sizing: border-box; }
    body { font-family: sans-serif; margin: 0; padding: 6mm; background: #eee; }
    .band { display: flex; align-items: center; gap: 4mm; width: 200mm; height: 28mm; padding: 2mm 4mm; border: 1px solid #000; border-radius: 4mm; background: #fff; }
    .band .name { font-size: 15pt; font-weight: 700; line-height: 1.1; }
    .band .line { font-size: 9.5pt; }
    .band .grow { flex: 1; min-width: 0; }
    .band .code svg { height: 18mm; width: auto; max-width: 70mm; }
    .allergy { border: 2px solid #000; padding: 0 3px; font-weight: 700; font-size: 9pt; }
    .bar { margin-bottom: 4mm; }
    @media print { body { background: #fff; padding: 0; } .bar { display: none; } .band { border: 0; } }
</style></head>
<body>
    <div class="bar"><button onclick="window.print()">{{ __('Print') }}</button> <span style="font-size:12px">{{ __('Paper size: wristband 200 × 28 mm. Choose "no margins" in the print window.') }}</span></div>
    <div class="band">
        <div class="grow">
            <div class="name">{{ $patient->full_name }}</div>
            <div class="line">{{ $patient->enterprise_patient_no }} · {{ $patient->date_of_birth ? \App\Support\Dashboard\Numbers::digits($patient->date_of_birth->format('d M Y')) : '—' }} · {{ $patient->sex ?? '—' }}@if($patient->blood_group) · {{ $patient->blood_group }}@endif</div>
            @if($patient->allergies->isNotEmpty())<div class="line"><span class="allergy">{{ __('ALLERGY') }}</span> {{ $patient->allergies->pluck('substance')->take(4)->implode(', ') }}</div>@endif
        </div>
        <div class="code">{!! $barcode !!}</div>
    </div>
</body></html>
