@extends('layouts.adminlte')

@section('page_title', __('Prescription').' '.$prescription->prescription_no)

@section('page_content')
    @php
        $p = $prescription->patient;
        $d = fn ($v) => \App\Support\Dashboard\Numbers::digits((string) $v);
        $status = (string) $prescription->status;
    @endphp
    <style>@media print { .rx-sheet { border: 0 !important; padding: 0 !important; } } .rx-sheet { background: #fff; border: 1px solid #ddd; padding: 12mm; max-width: 210mm; margin: 0 auto; min-height: 230mm; position: relative; } .rx-mark { font-size: 30pt; font-weight: 700; font-family: serif; } .rx-stamp { position: absolute; top: 40%; left: 20%; font-size: 60pt; font-weight: 700; color: rgba(150,150,150,.25); transform: rotate(-25deg); pointer-events: none; }</style>

    <div class="d-flex flex-wrap gap-2 mb-3 d-print-none">
        <button type="button" class="btn btn-sm btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> {{ __('Print') }}</button>
        @if($prescription->encounter)<a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.encounters.show', $prescription->encounter) }}">{{ __('Back to the visit') }}</a>@endif
        @if($status !== 'issued')<span class="text-warning small align-self-center"><i class="bi bi-exclamation-triangle"></i> {{ __('This prescription is not issued yet; the printout is marked so.') }}</span>@endif
    </div>

    <div class="rx-sheet">
        @if($status !== 'issued')<div class="rx-stamp">{{ strtoupper($status === 'draft' ? __('DRAFT') : __('CANCELLED')) }}</div>@endif

        <div class="d-flex justify-content-between border-bottom pb-2 mb-3">
            <div>
                <div class="fw-bold fs-5">{{ $prescription->doctor?->name ?? $provider?->name ?? '—' }}</div>
                <div class="small">{{ $provider?->specialty?->name }}</div>
                @if($provider?->license_number)<div class="small">{{ __('Registration no.') }}: {{ $provider->license_number }}</div>@endif
            </div>
            <div class="text-end small">
                <div><strong>{{ $prescription->prescription_no }}</strong></div>
                <div>{{ $d(($prescription->issued_at ?? $prescription->prescribed_at)?->format('d M Y, H:i') ?? '—') }}</div>
            </div>
        </div>

        <table class="table table-sm table-borderless mb-3">
            <tr><th style="width:15%">{{ __('Patient') }}</th><td>{{ $p?->full_name }}</td><th style="width:15%">{{ __('Patient number') }}</th><td>{{ $p?->enterprise_patient_no }}</td></tr>
            <tr><th>{{ __('Age / sex') }}</th><td>{{ $p?->date_of_birth ? $d($p->date_of_birth->age) : '—' }} / {{ $p?->sex ?? '—' }}</td><th>{{ __('Phone') }}</th><td>{{ $p?->phone ?? '—' }}</td></tr>
        </table>

        @if($prescription->clinical_notes)<div class="mb-3"><div class="fw-semibold small text-uppercase">{{ __('Clinical notes') }}</div><div>{{ $prescription->clinical_notes }}</div></div>@endif

        <div class="rx-mark">℞</div>
        <table class="table table-sm">
            <thead><tr><th>#</th><th>{{ __('Medicine') }}</th><th>{{ __('Strength') }}</th><th>{{ __('Frequency') }}</th><th>{{ __('Duration') }}</th><th class="text-end">{{ __('Qty') }}</th></tr></thead>
            <tbody>
                @forelse($prescription->items as $i => $item)
                    <tr>
                        <td>{{ $d($i + 1) }}</td>
                        <td><strong>{{ $item->medicine_name }}</strong>@if($item->dosage_form) <span class="small">({{ $item->dosage_form }})</span>@endif @if($item->instructions || $item->notes)<div class="small">{{ $item->instructions }} {{ $item->notes }}</div>@endif</td>
                        <td>{{ $item->strength ?? '' }}</td><td>{{ $item->frequency }}</td><td>{{ $item->duration }}</td><td class="text-end">{{ $item->quantity !== null ? $d($item->quantity) : '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">{{ __('No medicines on this prescription.') }}</td></tr>
                @endforelse
            </tbody>
        </table>

        @if($prescription->advice)<div class="mt-3"><div class="fw-semibold small text-uppercase">{{ __('Advice') }}</div><div>{{ $prescription->advice }}</div></div>@endif

        <div class="d-flex justify-content-end mt-5 pt-5"><div style="width:45%; border-top:1px solid #000" class="pt-1 text-center small">{{ $prescription->doctor?->name }}<br>{{ __('Signature and seal') }}</div></div>
    </div>
@stop
