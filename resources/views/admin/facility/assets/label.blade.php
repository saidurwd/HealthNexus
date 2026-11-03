<!doctype html><html lang="en"><head><meta charset="utf-8"><title>{{ $a->tag }}</title>
<style>body{font-family:sans-serif;margin:0;padding:12px}.label{display:flex;gap:10px;align-items:center;border:1px solid #000;border-radius:6px;padding:8px 10px;width:340px}.tag{font-size:22px;font-weight:bold}.small{font-size:11px;color:#333}@media print{button{display:none}}</style></head>
<body><div class="label"><div>{!! $qr !!}</div><div><div class="tag">{{ $a->tag }}</div><div>{{ $a->name }}</div><div class="small">{{ $a->location }}</div><div class="small">{{ __('Scan to report a fault or see its history') }}</div></div></div>
<p><button onclick="window.print()">{{ __('Print') }}</button></p></body></html>
