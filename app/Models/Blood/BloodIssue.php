<?php

namespace App\Models\Blood;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BloodIssue extends Model
{
    use HasFactory, HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['issued_at' => 'datetime', 'returned_at' => 'datetime', 'is_emergency' => 'boolean', 'crossmatched' => 'boolean', 'return_accepted' => 'boolean'];

    /** @return BelongsTo<BloodUnit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(BloodUnit::class, 'unit_id');
    }

    /** @return BelongsTo<BloodRequest, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(BloodRequest::class, 'request_id');
    }

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
