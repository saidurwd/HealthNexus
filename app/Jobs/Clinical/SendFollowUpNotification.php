<?php

namespace App\Jobs\Clinical;

use App\Models\Encounter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFollowUpNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Encounter $encounter) {}

    public function handle(): void
    {
        // Follow-up notification logic will be implemented here.
    }
}
