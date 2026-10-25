<?php

namespace App\Jobs\Icu;

use App\Models\Icu\IcuEpisode;
use App\Models\User;
use App\Services\Icu\IcuChargeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Daily job: creates/charges the day's icu_charge_events rows for every active episode
 * (bed-day always, ventilator-day only if a ventilator event exists today) — idempotent via the
 * icu_charge_events unique constraint, mirroring IPD's own daily bed-charge precedent.
 */
class GenerateIcuChargeEvents implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(IcuChargeService $charges): void
    {
        $today = now()->toDateString();

        IcuEpisode::query()->whereNull('discharged_at')->with('company')->each(function (IcuEpisode $episode) use ($charges, $today) {
            $user = User::whereHas('companies', fn ($q) => $q->where('companies.id', $episode->company_id))->first();

            if (! $user) {
                return;
            }

            $charges->chargeBedDay($episode, $today, $user);

            $ventilatedToday = $episode->ventilatorEvents()->whereDate('recorded_at', $today)->exists();

            if ($ventilatedToday) {
                $charges->chargeVentilatorDay($episode, $today, $user);
            }
        });
    }
}
