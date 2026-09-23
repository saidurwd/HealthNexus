<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientAmendment extends Model
{
    protected $fillable = [
        'company_id',
        'patient_id',
        'workflow_instance_id',
        'proposed_changes',
        'original_values',
        'reason',
        'status',
        'requested_by',
        'applied_at',
    ];

    protected $casts = [
        'proposed_changes' => 'array',
        'original_values' => 'array',
        'applied_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function isApplied(): bool
    {
        return $this->status === 'applied';
    }
}
