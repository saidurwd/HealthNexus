@extends('layouts.adminlte')

@section('page_title', __('Discharge summary'))

@section('page_content')
    @php
        $r = $dischargeRequest;
        $a = $r->admission;
        $p = $r->patient;
        $d = fn ($v) => \App\Support\Dashboard\Numbers::digits((string) $v);
        $firstBed = $a?->allocations->sortBy('id')->first()?->bed;
        $lastBed = $a?->allocations->sortByDesc('id')->first()?->bed;
        $from = $a?->admitted_at;
        $to = $r->completed_at ?? $a?->actual_discharge_date ?? $r->planned_date;
        $days = ($from && $to) ? max(1, (int) $from->copy()->startOfDay()->diffInDays(\Illuminate\Support\Carbon::parse($to)->startOfDay())) : null;
        $final = $r->status === 'completed';
    @endphp
    <style>@media print { .ds-sheet { border: 0 !important; padding: 0 !important; } } .ds-sheet { background: #fff; border: 1px solid #ddd; padding: 12mm; max-width: 210mm; margin: 0 auto; position: relative; } .ds-stamp { position: absolute; top: 35%; left: 18%; font-size: 52pt; font-weight: 700; color: rgba(150,150,150,.25); transform: rotate(-25deg); pointer-events: none; } .ds-h { font-size: 9pt; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid #999; margin: 14px 0 6px; }</style>

    <div class="d-flex flex-wrap gap-2 mb-3 d-print-none">
        <button type="button" class="btn btn-sm btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> {{ __('Print') }}</button>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.ipd.discharge.show', $r) }}">{{ __('Back to the discharge') }}</a>
        @unless($final)<span class="text-warning small align-self-center"><i class="bi bi-exclamation-triangle"></i> {{ __('The discharge is not completed yet; the printout is marked so.') }}</span>@endunless
    </div>

    <div class="ds-sheet">
        @unless($final)<div class="ds-stamp">{{ __('NOT FINAL') }}</div>@endunless
        <div class="h5 mb-3">{{ __('DISCHARGE SUMMARY') }}</div>

        <table class="table table-sm table-borderless mb-0">
            <tr><th style="width:18%">{{ __('Patient') }}</th><td>{{ $p?->full_name }}</td><th style="width:18%">{{ __('Patient number') }}</th><td>{{ $p?->enterprise_patient_no }}</td></tr>
            <tr><th>{{ __('Age / sex') }}</th><td>{{ $p?->date_of_birth ? $d($p->date_of_birth->age) : '—' }} / {{ $p?->sex ?? '—' }}</td><th>{{ __('Admission no.') }}</th><td>{{ $a?->admission_number }}</td></tr>
            <tr><th>{{ __('Admitted') }}</th><td>{{ $d($from?->format('d M Y, H:i') ?? '—') }}</td><th>{{ __('Discharged') }}</th><td>{{ $d($to ? \Illuminate\Support\Carbon::parse($to)->format('d M Y') : '—') }}@if($days) ({{ $d($days) }} {{ __('days') }})@endif</td></tr>
            <tr><th>{{ __('Ward / bed') }}</th><td>{{ $lastBed?->room?->ward?->name }} {{ $lastBed?->bed_code }}@if($firstBed && $lastBed && $firstBed->id !== $lastBed->id) <span class="small">({{ __('first bed') }} {{ $firstBed->bed_code }})</span>@endif</td><th>{{ __('Department') }}</th><td>{{ $a?->department?->name ?? '—' }}</td></tr>
            <tr><th>{{ __('Consultant') }}</th><td colspan="3">{{ $a?->attendingProvider?->name ?? '—' }}</td></tr>
        </table>

        <div class="ds-h">{{ __('Diagnosis at discharge') }}</div>
        <div>{{ $r->discharge_diagnosis ?: '—' }}</div>

        <div class="ds-h">{{ __('Condition and type of discharge') }}</div>
        <div>{{ ucfirst(str_replace('_', ' ', (string) $r->discharge_type)) }}@if($r->disposition) — {{ $r->disposition->name }}@endif</div>
        @if($r->reason)<div class="small">{{ $r->reason }}</div>@endif

        <div class="ds-h">{{ __('Medicines to continue') }}</div>
        @if($medicines->isEmpty())
            <div class="text-muted">{{ __('None recorded.') }}</div>
        @else
            <table class="table table-sm">
                <thead><tr><th>#</th><th>{{ __('Medicine') }}</th><th>{{ __('Strength') }}</th><th>{{ __('Frequency') }}</th><th>{{ __('Duration') }}</th></tr></thead>
                <tbody>@foreach($medicines as $i => $m)<tr><td>{{ $d($i + 1) }}</td><td>{{ $m->medicine_name }}@if($m->instructions)<div class="small">{{ $m->instructions }}</div>@endif</td><td>{{ $m->strength }}</td><td>{{ $m->frequency }}</td><td>{{ $m->duration }}</td></tr>@endforeach</tbody>
            </table>
        @endif

        <div class="ds-h">{{ __('Advice') }}</div>
        <div style="white-space: pre-line">{{ $r->instructions ?: '—' }}</div>

        <div class="ds-h">{{ __('Follow-up') }}</div>
        <div>@if($r->follow_up_required){{ __('Please come back') }}@if($r->follow_up_date) {{ __('on') }} <strong>{{ $d($r->follow_up_date->format('d M Y')) }}</strong>@endif @if($r->followUpProvider) {{ __('to') }} {{ $r->followUpProvider->name }}@endif @else {{ __('No follow-up needed.') }} @endif</div>
        <div class="small mt-2"><i class="bi bi-exclamation-circle"></i> {{ __('Come to the emergency department at once if the condition worsens.') }}</div>

        <div class="d-flex justify-content-between mt-5 pt-4 small">
            <div style="width:40%; border-top:1px solid #000" class="pt-1">{{ __('Patient / guardian signature') }}</div>
            <div style="width:40%; border-top:1px solid #000" class="pt-1 text-end">{{ $a?->attendingProvider?->name }}<br>{{ __('Signature and seal') }}</div>
        </div>
    </div>
@stop
