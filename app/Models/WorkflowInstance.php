<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WorkflowInstance extends Model
{
    protected $fillable = [
        'workflow_id', 'company_id', 'branch_id', 'subject_type', 'subject_id',
        'status', 'current_step_id', 'initiated_by', 'submitted_at', 'completed_at', 'notes',
    ];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'current_step_id');
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowAction::class)->latest();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['approved', 'rejected', 'completed', 'cancelled'], true);
    }

    public function isPendingApproval(): bool
    {
        return $this->status === 'pending_approval';
    }
}
