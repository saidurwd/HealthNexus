<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>{{ __($form['title']) }}</title>
<style>
    @page { size: A4 portrait; margin: 12mm }
    body { font: 13px/1.5 'Noto Sans Bengali','Kohinoor Bangla','Bangla Sangam MN','Nirmala UI',sans-serif; color: #000 }
    h1 { font-size: 18px; text-align: center; margin: 4px 0 } .lh { text-align: center; margin-bottom: 4px }
    .note { border: 1px solid #000; padding: 3px 6px; margin: 6px 0 10px; font-size: 11px }
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 20px } .field { border-bottom: 1px solid #000; min-height: 26px }
    .field small { display: block; font-size: 10px }
    table { width: 100%; border-collapse: collapse; margin-top: 10px } th, td { border: 1px solid #000; padding: 3px; font-size: 11px; height: 24px }
    .stamp { border: 2px solid #000; text-align: center; font-weight: bold; margin: 4px 0 }
</style></head><body>
<div class="lh"><strong>{{ $letterhead['name'] }}</strong>@if($letterhead['name_bn']) · {{ $letterhead['name_bn'] }}@endif<br>{{ $letterhead['address'] }}</div>
@if($training)<div class="stamp">TRAINING</div>@endif
<h1>{{ $label($form['title']) }}</h1>
<div class="note">{{ $label('Use this form only while the system is unavailable. Write clearly. Back-enter into the system later with the original time and mark it as entered late.') }}</div>
<div class="grid">
    @foreach($form['fields'] as $field)
        <div class="field"><small>{{ $label($field) }}</small></div>
    @endforeach
</div>
@isset($form['table'])
    <table>
        <tr>@foreach($form['table']['columns'] as $column)<th>{{ $label($column) }}</th>@endforeach</tr>
        @for($i = 0; $i < $form['table']['rows']; $i++)<tr>@foreach($form['table']['columns'] as $column)<td></td>@endforeach</tr>@endfor
    </table>
@endisset
@foreach($form['footer'] ?? [] as $field)
    <div class="field" style="margin-top:14px"><small>{{ $label($field) }}</small></div>
@endforeach
</body></html>
