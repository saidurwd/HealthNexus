<?php

namespace App\Services\Ot;

use App\Models\Ot\OtPreoperativeChecklist;
use App\Models\Ot\OtPreoperativeChecklistItem;
use App\Models\Ot\OtSurgery;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Validation\ValidationException;

class OtPreoperativeChecklistService
{
    public function __construct(private readonly SettingsService $settings) {}

    public function initialize(OtSurgery $surgery): OtPreoperativeChecklist
    {
        $checklist = OtPreoperativeChecklist::firstOrCreate(
            ['surgery_id' => $surgery->id],
            ['status' => OtPreoperativeChecklist::STATUS_PENDING],
        );

        if ($checklist->items()->doesntExist()) {
            foreach ($this->settings->get('ot.preop_checklist_items', []) as $item) {
                $checklist->items()->create([
                    'category' => $item['category'],
                    'label' => $item['label'],
                    'is_required' => $item['is_required'] ?? true,
                ]);
            }
        }

        return $checklist->refresh()->load('items');
    }

    /**
     * Refuses to mark a required item complete without a response (spec §25) — an explicit
     * exception path is still allowed, but only with a reason, never a silent completion.
     */
    public function completeItem(OtPreoperativeChecklistItem $item, User $user, ?string $response, ?string $exceptionReason = null): OtPreoperativeChecklistItem
    {
        if ($item->is_required && ! $response && ! $exceptionReason) {
            throw ValidationException::withMessages(['response' => "'{$item->label}' is required — provide a response or an explicit exception reason."]);
        }

        $item->update([
            'response' => $response,
            'exception_reason' => $exceptionReason,
            'completed_by' => $user->id,
            'completed_at' => now(),
        ]);

        return $item->refresh();
    }

    public function complete(OtPreoperativeChecklist $checklist, User $user): OtPreoperativeChecklist
    {
        $incomplete = $checklist->items()
            ->where('is_applicable', true)
            ->where('is_required', true)
            ->whereNull('completed_at')
            ->exists();

        if ($incomplete) {
            throw ValidationException::withMessages(['checklist' => 'All required, applicable items must be completed first.']);
        }

        $checklist->update(['status' => OtPreoperativeChecklist::STATUS_COMPLETED, 'completed_at' => now(), 'completed_by' => $user->id]);

        return $checklist->refresh();
    }
}
