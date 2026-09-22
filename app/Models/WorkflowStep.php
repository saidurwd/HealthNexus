<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowStep extends Model
{
    protected $fillable = ['workflow_id', 'step_order', 'name', 'is_final'];

    protected function casts(): array
    {
        return ['step_order' => 'integer', 'is_final' => 'boolean'];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function approvers(): HasMany
    {
        return $this->hasMany(WorkflowApprover::class);
    }

    public function nextStep(): ?self
    {
        return static::query()
            ->where('workflow_id', $this->workflow_id)
            ->where('step_order', '>', $this->step_order)
            ->orderBy('step_order')
            ->first();
    }
}
