<?php

namespace Modules\Interop\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interop\WebhookSigner;
use App\Models\Integration\IntegrationPartner;
use App\Services\Interop\ExchangeGuard;
use App\Services\Interop\MessageService;
use App\Services\ModuleManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Where a partner posts events to us. There is no login: the signature IS the credential (HMAC over timestamp + body with a secret only the partner and we hold). A body that was changed, a request
 * that is too old or too new, and a delivery id seen before are all refused. The notice is only stored; nothing is done with it automatically.
 */
class WebhookController extends Controller
{
    public function __construct(private readonly WebhookSigner $signer, private readonly MessageService $messages, private readonly ModuleManager $modules, private readonly ExchangeGuard $guard) {}

    public function receive(Request $request, string $partner): JsonResponse
    {
        $row = IntegrationPartner::query()->where('public_id', $partner)->where('status', 'active')->whereIn('direction', ['in', 'both'])->first();
        if ($row === null || ! $this->modules->isEnabled('interop', $row->organization_id)) {
            return response()->json(['message' => 'Unknown partner.'], 404); // the same answer whether the partner exists or not
        }
        $body = $request->getContent();
        $check = $this->signer->verify($row, $body, $request->header(config('interop.webhook.timestamp_header')), $request->header(config('interop.webhook.signature_header')), $request->header(config('interop.webhook.delivery_header')));
        if (! $check['ok']) {
            $this->guard->log($row, null, 'Webhook', null, 'receive', 'denied_scope', $check['reason']);

            return response()->json(['message' => 'The request could not be verified.'], 401);
        }
        $decoded = json_decode($body, true);
        [$m] = $this->messages->receive($row->organization_id, $row, 'webhook', 'webhook:'.mb_substr((string) ($decoded['event'] ?? 'unknown'), 0, 30), $body, (string) $request->header(config('interop.webhook.delivery_header')));
        $m->update(['status' => 'processed', 'processed_at' => now()]);

        return response()->json(['received' => $m->public_id], 202);
    }
}
