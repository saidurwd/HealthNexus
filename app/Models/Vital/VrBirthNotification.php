<?php

namespace App\Models\Vital;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use Database\Factories\Vital\VrBirthNotificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VrBirthNotification extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): VrBirthNotificationFactory
    {
        return VrBirthNotificationFactory::new();
    }

    protected $table = 'vr_birth_notifications';

    protected $guarded = ['id'];

    protected $casts = ['born_at' => 'datetime', 'issued_at' => 'datetime', 'registered_at' => 'datetime', 'cancelled_at' => 'datetime', 'father_nid' => 'encrypted'];

    /** @return BelongsTo<Patient, $this> */
    public function mother(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'mother_patient_id');
    }

    /** @return BelongsTo<Patient, $this> */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'child_patient_id');
    }
}
