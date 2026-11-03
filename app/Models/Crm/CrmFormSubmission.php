<?php

namespace App\Models\Crm;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmFormSubmission extends Model
{
    use HasPublicId;

    protected $table = 'crm_form_submissions';

    protected $guarded = ['id'];

    protected $casts = ['answers' => 'encrypted:array', 'reviewed_at' => 'datetime'];

    /** @return BelongsTo<CrmForm, $this> */
    public function form(): BelongsTo
    {
        return $this->belongsTo(CrmForm::class, 'form_id');
    }

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
