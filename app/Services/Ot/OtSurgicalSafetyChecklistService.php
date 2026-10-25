<?php

namespace App\Services\Ot;

use App\Events\Ot\SurgicalChecklistCompleted;
use App\Models\Ot\OtSurgery;
use App\Models\Ot\OtSurgicalSafetyChecklist;
use App\Models\Ot\OtSurgicalSafetyChecklistItem;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Validation\ValidationException;

/**
 * WHO-style Sign In / Time Out / Sign Out checklist (spec §24/§25). completeItem() carries the
 * same mandatory-response guard as the preoperative checklist, plus an explicit, audited
 * exception path — never a silent completion.
 */
class OtSurgicalSafetyChecklistService
{
    public function __construct(private readonly SettingsService $settings) {}

    public function initializePhase(OtSurgery $surgery, string $phase): OtSurgicalSafetyChecklist
    {
        $checklist = OtSurgicalSafetyChecklist::firstOrCreate(
            ['surgery_id' => $surgery->id, 'phase' => $phase],
            ['status' => OtSurgicalSafetyChecklist::STATUS_PENDING],
        );

        if ($checklist->items()->doesntExist()) {
            $items = $this->settings->get('ot.safety_checklist_items', [])[$phase] ?? [];

            foreach ($items as $label) {
                $checklist->items()->create(['label' => $label, 'is_required' => true]);
            }
        }

        return $checklist->refresh()->load('items');
    }

    public function completeItem(OtSurgicalSafetyChecklistItem $item, User $user, ?string $response, ?string $exceptionReason = null, ?string $comment = null): OtSurgicalSafetyChecklistItem
    {
        if ($item->is_required && ! $response && ! $exceptionReason) {
            throw ValidationException::withMessages(['response' => "'{$item->label}' is required — provide a response or an explicit, audited exception reason."]);
        }

        $item->update([
            'response' => $response,
            'exception_reason' => $exceptionReason,
            'comment' => $comment,
            'completed_by' => $user->id,
            'completed_at' => now(),
        ]);

        return $item->refresh();
    }

    public function completePhase(OtSurgicalSafetyChecklist $checklist, User $user): OtSurgicalSafetyChecklist
    {
        $incomplete = $checklist->items()->where('is_required', true)->whereNull('completed_at')->exists();

        if ($incomplete) {
            throw ValidationException::withMessages(['checklist' => "All required items in the '{$checklist->phase}' phase must be completed first."]);
        }

        $checklist->update(['status' => OtSurgicalSafetyChecklist::STATUS_COMPLETED, 'completed_at' => now(), 'completed_by' => $user->id]);

        event(new SurgicalChecklistCompleted($checklist));

        return $checklist->refresh();
    }

    public function isPhaseComplete(OtSurgery $surgery, string $phase): bool
    {
        return OtSurgicalSafetyChecklist::where('surgery_id', $surgery->id)
            ->where('phase', $phase)
            ->where('status', OtSurgicalSafetyChecklist::STATUS_COMPLETED)
            ->exists();
    }
}
