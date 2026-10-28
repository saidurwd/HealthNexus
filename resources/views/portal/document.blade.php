@extends('portal.layout')
@section('title', $doc->title)
@section('content')
<h1>{{ $doc->title }}</h1>
<p class="muted noprint">{{ __('This link works until :d. Use your browser\'s print command to save a copy.', ['d' => $doc->expires_at->format('d M Y')]) }}</p>
<div class="card">
@if($c['type'] === 'lab_report')<table><thead><tr><th>{{ __('Test') }}</th><th>{{ __('Result') }}</th><th>{{ __('Normal range') }}</th></tr></thead><tbody>@foreach($c['data']['rows'] as $row)<tr><td>{{ $row['test'] }}</td><td>{{ $row['value'] }} {{ $row['unit'] }} {{ $row['flag'] }}</td><td>{{ $row['range'] }}</td></tr>@endforeach</tbody></table>
@elseif($c['type'] === 'radiology_report')<p style="white-space:pre-wrap">{{ $c['data']['findings'] }}</p><p><strong>{{ $c['data']['impression'] }}</strong></p>
@elseif($c['type'] === 'receipt')<p>{{ __('Receipt') }} {{ $c['data']->receipt_number }} · {{ $c['data']->issued_at?->format('d M Y') }}</p><p>{{ __('Amount') }}: <strong>{{ number_format((float) $c['data']->payment?->amount, 2) }}</strong> · {{ __('Bill') }} {{ $c['data']->invoice?->invoice_number }}</p>
@else<p>{{ $c['data']->prescription_no }} · {{ $c['data']->issued_at?->format('d M Y') }} · {{ $c['data']->doctor?->name }}</p><table><tbody>@foreach($c['data']->items as $i)<tr><td>{{ $i->medicine_name }} {{ $i->strength }}</td><td>{{ $i->frequency }}</td><td>{{ $i->duration }}</td><td class="muted">{{ $i->instructions }}</td></tr>@endforeach</tbody></table>@endif
</div>
<button class="noprint" onclick="window.print()">{{ __('Print') }}</button>
@endsection
