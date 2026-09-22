<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowAction extends Model
{
    public const SUBMIT = 'submit';

    public const APPROVE = 'approve';

    public const REJECT = 'reject';

    public const RETURN_ = 'return';

    public const COMPLETE = 'complete';

    public const CANCEL = 'cancel';

    protected $fillable = ['workflow_instance_id', 'workflow_step_id', 'action', 'performed_by', 'note'];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'workflow_step_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
