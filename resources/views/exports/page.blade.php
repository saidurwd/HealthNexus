<!doctype html>
<html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"><title>{{ $title }}</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: "Noto Sans Bengali", "Noto Sans", "Helvetica Neue", Arial, sans-serif; font-size: 9pt; color: #111; margin: 0; }
    .head { border-bottom: 2px solid #111; padding-bottom: 3mm; margin-bottom: 4mm; }
    .org { font-size: 15pt; font-weight: 700; } .branch { font-size: 9pt; color: #444; }
    .title { font-size: 12pt; font-weight: 600; margin-top: 2mm; }
    .meta { font-size: 8pt; color: #444; margin-top: 1mm; }
    h2 { font-size: 10.5pt; margin: 5mm 0 1.5mm; break-after: avoid; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 3mm; }
    thead { display: table-header-group; } tr { break-inside: avoid; }
    th, td { border: 1px solid #999; padding: 1.4mm 1.8mm; text-align: left; vertical-align: top; word-break: break-word; }
    th { background: #e8edf3; font-weight: 700; }
    tbody tr:nth-child(even) td { background: #f7f7f7; }
    .conf { margin-top: 4mm; font-size: 7.5pt; color: #555; }
</style></head>
<body>
    <div class="head">
        <div class="org">{{ $organisation }}</div>
        @if($branch)<div class="branch">{{ $branch }}</div>@endif
        <div class="title">{{ $title }}</div>
        <div class="meta">{{ $by }}@if($filters) · {{ $filters }}@endif</div>
    </div>
    @forelse($tables as $table)
        @if($table['name'] !== $title || count($tables) > 1)<h2>{{ $table['name'] }}</h2>@endif
        <table>
            @if($table['header'])<thead><tr>@foreach($table['header'] as $h)<th>{{ $h }}</th>@endforeach</tr></thead>@endif
            <tbody>@foreach($table['rows'] as $row)<tr>@foreach($row as $v)<td>{{ $v }}</td>@endforeach</tr>@endforeach</tbody>
        </table>
    @empty
        <p>{{ __('There is nothing in a table form to export on this page.') }}</p>
    @endforelse
    <div class="conf">{{ __('CONFIDENTIAL — contains patient information. Do not share or forward.') }}</div>
</body></html>
