<!doctype html>
<html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"><title>{{ $specimen->accession_number }}</title>
<style>
    @page { size: 50mm 30mm; margin: 0; }
    * { box-sizing: border-box; }
    body { font-family: sans-serif; margin: 0; padding: 6mm; background: #eee; }
    .tube { width: 50mm; height: 30mm; padding: 1.5mm 2mm; background: #fff; border: 1px solid #000; overflow: hidden; }
    .tube .name { font-size: 8.5pt; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tube .line { font-size: 6.5pt; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tube .code { text-align: center; } .tube .code svg { height: 11mm; max-width: 46mm; width: auto; }
    .bar { margin-bottom: 4mm; }
    @media print { body { background: #fff; padding: 0; } .bar { display: none; } .tube { border: 0; } }
</style></head>
<body>
    @php $patient = $specimen->labOrder?->patient; @endphp
    <div class="bar"><button onclick="window.print()">{{ __('Print') }}</button> <span style="font-size:12px">{{ __('Paper size: label 50 × 30 mm.') }}</span></div>
    <div class="tube">
        <div class="name">{{ $patient?->full_name }}</div>
        <div class="line">{{ $patient?->enterprise_patient_no }} · {{ $patient?->date_of_birth ? \App\Support\Dashboard\Numbers::digits((string) $patient->date_of_birth->age) : '' }} {{ $patient?->sex }}</div>
        <div class="code">{!! $barcode !!}</div>
        <div class="line">{{ $specimen->specimenType?->name }} · {{ $specimen->orderItems->map(fn ($i) => $i->test?->code ?? $i->test?->name)->filter()->take(4)->implode(', ') }}</div>
        <div class="line">{{ \App\Support\Dashboard\Numbers::digits($specimen->collected_at?->format('d M Y H:i') ?? '') }}</div>
    </div>
</body></html>
