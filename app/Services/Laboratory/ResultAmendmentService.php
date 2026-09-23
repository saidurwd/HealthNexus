<?php

namespace App\Services\Laboratory;

use App\Events\Laboratory\LabReportAmended;
use App\Models\Laboratory\LabResult;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Never overwrites a reported/validated result (spec §30): amend() creates a new row
 * (version+1, amended_from_id pointing at the row it supersedes) and flips the prior row's
 * is_current to false. The original stays exactly as it was — full history via LabResult::versions().
 */
class ResultAmendmentService
{
    public function __construct(private readonly LabOrderLifecycleService $lifecycle) {}

    /**
     * @param  array{numeric_value?:string|null,text_value?:string|null,qualitative_value?:string|null,unit?:string|null,abnormal_flag?:string|null,critical_flag?:bool}  $data
     */
    public function amend(LabResult $result, array $data, string $reason, User $user): LabResult
    {
        return DB::transaction(function () use ($result, $data, $reason, $user) {
            if (! $result->is_current) {
                throw ValidationException::withMessages(['result' => 'Only the current version of a result can be amended.']);
            }

            if (in_array($result->result_status, ['pending', 'cancelled'], true)) {
                throw ValidationException::withMessages(['result' => "Cannot amend a result in '{$result->result_status}' status."]);
            }

            $wasReported = $result->isReported();

            $amended = LabResult::create([
                ...$result->only([
                    'company_id', 'branch_id', 'lab_order_item_id', 'test_id', 'specimen_id',
                    'result_type', 'unit', 'reference_range_low', 'reference_range_high', 'reference_range_text',
                ]),
                'numeric_value' => $data['numeric_value'] ?? $result->numeric_value,
                'text_value' => $data['text_value'] ?? $result->text_value,
                'qualitative_value' => $data['qualitative_value'] ?? $result->qualitative_value,
                'abnormal_flag' => $data['abnormal_flag'] ?? $result->abnormal_flag,
                'critical_flag' => $data['critical_flag'] ?? $result->critical_flag,
                'result_status' => $wasReported ? 'reported' : $result->result_status,
                'entered_by' => $user->id,
                'entered_at' => now(),
                'reported_at' => $wasReported ? now() : null,
                'version' => $result->version + 1,
                'is_current' => true,
                'amended_from_id' => $result->id,
                'amendment_reason' => $reason,
                'amended_by' => $user->id,
            ]);

            $result->update(['is_current' => false]);

            if ($wasReported) {
                $report = $result->orderItem->labOrder->reports()->whereIn('status', ['final', 'amended'])->latest()->first();

                if ($report) {
                    $report->update(['status' => 'amended', 'amended_by' => $user->id, 'amended_at' => now()]);
                    event(new LabReportAmended($report, $amended));
                }
            }

            return $amended;
        });
    }
}
