@extends('portal.layout')
@section('title', __('Medicines'))
@section('content')
<h1>{{ __('My medicines') }}</h1>
<p class="muted">{{ __('Ask for a refill here. A pharmacist or doctor reads every request; nothing is sent to you automatically.') }}</p>
@if($allergies->isNotEmpty())<div class="emerg">{{ __('Allergies on file') }}: {{ $allergies->pluck('substance')->implode(', ') }}. {{ __('Tell every doctor and pharmacist about them before you take a new medicine.') }}</div>@endif
@forelse($rows as $p)<div class="card"><div class="row"><div><strong>{{ $p->prescription_no }}</strong> <span class="muted">{{ $p->issued_at?->format('d M Y') }} · {{ $p->doctor?->name }}</span></div>
 <form method="POST" action="{{ route('portal.documents.share') }}" class="noprint">@csrf<input type="hidden" name="type" value="prescription"><input type="hidden" name="id" value="{{ $p->public_id }}"><button class="alt">{{ __('Printable copy') }}</button></form></div>
<table><tbody>@foreach($p->items as $i)<tr><td>{{ $i->medicine_name }} {{ $i->strength }}</td><td>{{ $i->frequency }}</td><td>{{ $i->duration }}</td></tr>@endforeach</tbody></table>
@if($p->advice)<p class="muted">{{ $p->advice }}</p>@endif
@php($rq = $requests[$p->id] ?? null)
@if($rq)<p><span class="pill {{ $rq->status === 'approved' ? 'good' : ($rq->status === 'requested' ? 'wait' : 'bad') }}">{{ __(\Illuminate\Support\Str::headline($rq->status)) }}</span> {{ $rq->decision_note }}</p>@endif
@if(! $rq || ! in_array($rq->status, ['requested']))<form method="POST" action="{{ route('portal.prescriptions.refill', $p->public_id) }}" class="noprint">@csrf<input type="hidden" name="key" value="{{ \Illuminate\Support\Str::uuid() }}"><input name="note" aria-label="{{ __('Note for the pharmacy (optional)') }}" placeholder="{{ __('Note for the pharmacy (optional)') }}" maxlength="300"><p><button class="alt">{{ __('Ask for a refill') }}</button></p></form>@endif</div>
@empty<p class="muted">{{ __('No prescriptions yet.') }}</p>@endforelse
@endsection
