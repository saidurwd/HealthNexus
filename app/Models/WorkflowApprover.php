<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowApprover extends Model
{
    public const TYPE_ROLE = 'role';

    public const TYPE_USER = 'user';

    public const TYPE_DEPARTMENT = 'department';

    protected $fillable = ['workflow_step_id', 'approver_type', 'role_name', 'user_id', 'department_id'];

    public function step(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'workflow_step_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function isEligible(User $user): bool
    {
        return match ($this->approver_type) {
            self::TYPE_USER => $this->user_id === $user->id,
            self::TYPE_ROLE => $this->role_name !== null && $user->hasRole($this->role_name),
            self::TYPE_DEPARTMENT => $this->department_id !== null && $user->departments()->where('departments.id', $this->department_id)->exists(),
            default => false,
        };
    }
}
