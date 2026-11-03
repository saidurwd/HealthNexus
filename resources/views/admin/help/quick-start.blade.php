<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>{{ $en['title'] }} — {{ $bn['title'] }}</title>
<style>
    @page { size: A4 portrait; margin: 12mm }
    body { font: 13px/1.5 'Noto Sans Bengali','Kohinoor Bangla','Bangla Sangam MN','Nirmala UI',sans-serif; color: #000 }
    h1 { font-size: 22px; margin: 0 } .goal { margin: 2px 0 10px } .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 18px }
    h2 { font-size: 15px; border-bottom: 2px solid #000; margin: 0 0 6px } ol { padding-left: 20px; margin: 0 0 8px } li { margin-bottom: 5px }
    .tips { border: 1px solid #000; padding: 4px 8px; font-size: 12px } .foot { margin-top: 10px; font-size: 11px; text-align: center }
</style></head><body>
<h1>{{ $en['title'] }} · {{ $bn['title'] }}</h1>
<div class="cols">
    <div>
        <div class="goal"><strong>{{ $en['goal'] }}</strong></div>
        <h2>Steps</h2><ol>@foreach($en['steps'] as $step)<li>{{ $step }}</li>@endforeach</ol>
        <div class="tips"><strong>Tips</strong><ul>@foreach($en['tips'] as $tip)<li>{{ $tip }}</li>@endforeach</ul></div>
    </div>
    <div>
        <div class="goal"><strong>{{ $bn['goal'] }}</strong></div>
        <h2>ধাপসমূহ</h2><ol>@foreach($bn['steps'] as $step)<li>{{ $step }}</li>@endforeach</ol>
        <div class="tips"><strong>পরামর্শ</strong><ul>@foreach($bn['tips'] as $tip)<li>{{ $tip }}</li>@endforeach</ul></div>
    </div>
</div>
<div class="foot">Search or scan: Ctrl+K · Shortcuts: ? · সার্চ বা স্ক্যান: Ctrl+K · শর্টকাট: ?</div>
</body></html>
