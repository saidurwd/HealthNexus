<?php

namespace App\Jobs\Icu;

use App\Models\Icu\IcuEpisode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateIcuDailyCensus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $today = now()->toDateString();

        $census = IcuEpisode::query()
            ->selectRaw('company_id, count(*) as current_patients')
            ->whereNull('discharged_at')
            ->groupBy('company_id')
            ->get();

        $admissionsToday = IcuEpisode::query()
            ->selectRaw('company_id, count(*) as admissions')
            ->whereDate('admitted_at', $today)
            ->groupBy('company_id')
            ->get();

        $dischargesToday = IcuEpisode::query()
            ->selectRaw('company_id, count(*) as discharges')
            ->whereDate('discharged_at', $today)
            ->groupBy('company_id')
            ->get();

        Log::channel('single')->info('ICU daily census', [
            'date' => $today,
            'current_patients' => $census->pluck('current_patients', 'company_id'),
            'admissions_today' => $admissionsToday->pluck('admissions', 'company_id'),
            'discharges_today' => $dischargesToday->pluck('discharges', 'company_id'),
        ]);
    }
}
