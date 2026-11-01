<?php

namespace App\Models\Quality;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaMortalityReview extends Model
{
    use HasPublicId;

    protected $table = 'qa_mortality_reviews';

    protected $guarded = ['id'];

    protected $casts = ['died_at' => 'datetime', 'reviewed_at' => 'datetime'];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
