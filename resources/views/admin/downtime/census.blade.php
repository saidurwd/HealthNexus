<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Downtime census — {{ $organization->name }}</title>
<style>
    @page { size: A4 landscape; margin: 10mm }
    body { font: 12px/1.35 'Noto Sans Bengali','Kohinoor Bangla','Bangla Sangam MN','Nirmala UI',sans-serif; color: #000 }
    h1 { font-size: 16px; margin: 0 } .meta { margin: 4px 0 8px } table { width: 100%; border-collapse: collapse; margin-bottom: 10px }
    th, td { border: 1px solid #000; padding: 3px 5px; vertical-align: top; text-align: left } th { background: #eee }
    .warn { border: 2px solid #000; padding: 4px; font-weight: bold; margin: 6px 0 } .allergy { font-weight: bold }
</style></head><body>
<h1>{{ $organization->name }} — {{ __('Downtime census') }} / Downtime census</h1>
<div class="meta">{{ __('Generated') }}: {{ $generatedAt->format('d M Y H:i') }} · {{ count($rows) }} {{ __('inpatients') }}</div>
<div class="warn">{{ __('READ-ONLY COPY. It may be out of date: check the patient and the bed before acting on it. Write new orders on the downtime forms.') }}</div>
@if($training)<div class="warn">TRAINING — NOT REAL PATIENTS</div>@endif
@foreach(collect($rows)->groupBy('ward') as $ward => $patients)
    <h2 style="font-size:14px;margin:8px 0 2px">{{ $ward }}</h2>
    <table>
        <tr><th>{{ __('Bed') }}</th><th>{{ __('Patient') }}</th><th>MRN</th><th>{{ __('Age') }}/{{ __('Sex') }}</th><th>{{ __('Doctor') }}</th><th>{{ __('Allergies') }}</th><th>{{ __('Medicines due (next 36 h)') }}</th></tr>
        @foreach($patients as $p)
            <tr>
                <td>{{ $p['bed'] }}</td>
                <td>{{ $p['name'] }}@if($p['alerts'])<br><small>⚠ {{ implode('; ', $p['alerts']) }}</small>@endif</td>
                <td>{{ $p['mrn'] }}<br><small>{{ $p['admission_number'] }}</small></td>
                <td>{{ $p['age_sex'] }}</td><td>{{ $p['doctor'] }}</td>
                <td class="allergy">{{ $p['allergies'] ? implode(', ', $p['allergies']) : '—' }}</td>
                <td>{!! $p['medicines'] ? e(implode('; ', $p['medicines'])) : '—' !!}</td>
            </tr>
        @endforeach
    </table>
@endforeach
</body></html>
