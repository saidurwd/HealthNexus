<?php

namespace App\Models\Nursing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NursingMedicationAdministrationCorrection extends Model
{
    protected $fillable = ['administration_id', 'correction_type', 'reason', 'previous_values', 'created_by'];

    protected function casts(): array
    {
        return ['previous_values' => 'array'];
    }

    public function administration(): BelongsTo
    {
        return $this->belongsTo(NursingMedicationAdministration::class, 'administration_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
