<?php

namespace App\Console\Commands\Interop;

use App\Models\Integration\IntegrationAccessLog;
use App\Models\Integration\IntegrationMessage;
use App\Models\Integration\IntegrationNonce;
use App\Models\Organization;
use App\Services\Interop\CredentialService;
use App\Services\Interop\DicomWorklistService;
use App\Services\Interop\InteropPolicy;
use App\Services\Interop\MessageService;
use App\Services\ModuleManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;

/** Housekeeping for integration: stuck messages go back to the queue, rotated-out secrets stop working, the modality worklist follows the radiology exams, old payloads and logs are cleared by the retention rules. */
class CheckInterop extends Command
{
    protected $signature = 'interop:check';

    protected $description = 'Release stuck messages, end rotated secrets, sync the DICOM worklist, apply retention';

    public function handle(MessageService $messages, CredentialService $credentials, DicomWorklistService $dicom, InteropPolicy $policy, ModuleManager $modules): int
    {
        $stuck = $messages->releaseStuck();
        $ended = $credentials->expireOld();
        $made = $updated = 0;
        foreach (Organization::query()->where('is_active', true)->get() as $org) {
            if ($modules->isEnabled('interop', $org->id) && $modules->isEnabled('radiology', $org->id)) {
                $r = $dicom->sync($org->id);
                $made += $r['created'];
                $updated += $r['updated'];
            }
        }
        // Retention: the message record stays, the (encrypted) payload goes after its period; nonces and old access-log rows go.
        $processed = IntegrationMessage::query()->where('status', 'processed')->where('processed_at', '<', now()->subDays($policy->int('retention.processed_payload_days', 90)))->whereNull('purged_at')->update(['payload' => Crypt::encryptString('[purged]'), 'response' => null, 'purged_at' => now()]);
        $dead = IntegrationMessage::query()->whereIn('status', ['dead', 'dismissed'])->where('updated_at', '<', now()->subDays($policy->int('retention.dead_payload_days', 365)))->whereNull('purged_at')->update(['payload' => Crypt::encryptString('[purged]'), 'response' => null, 'purged_at' => now()]);
        $nonces = IntegrationNonce::query()->where('seen_at', '<', now()->subHours($policy->int('retention.nonce_hours', 48)))->delete();
        $logs = IntegrationAccessLog::query()->where('created_at', '<', now()->subDays($policy->int('retention.access_log_days', 730)))->delete();
        $this->info("$stuck stuck message(s) requeued, $ended secret(s) ended, worklist: $made new / $updated changed, payloads purged: $processed processed + $dead dead, $nonces nonce(s), $logs old log row(s).");

        return self::SUCCESS;
    }
}
