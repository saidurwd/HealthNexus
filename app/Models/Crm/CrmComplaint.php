<?php

namespace App\Models\Crm;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmComplaint extends Model
{
    use HasFactory, HasPublicId;

    protected $table = 'crm_complaints';

    protected $guarded = ['id'];

    protected $casts = ['due_at' => 'datetime', 'resolved_at' => 'datetime', 'closed_at' => 'datetime'];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return HasMany<CrmComplaintEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(CrmComplaintEvent::class, 'complaint_id');
    }
}
