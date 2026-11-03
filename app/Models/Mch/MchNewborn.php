<?php

namespace App\Models\Mch;

use App\Models\Patient;
use Database\Factories\Mch\MchNewbornFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MchNewborn extends Model
{
    use HasFactory;

    protected static function newFactory(): MchNewbornFactory
    {
        return MchNewbornFactory::new();
    }

    protected $table = 'mch_newborns';

    protected $guarded = ['id'];

    protected $casts = ['breastfed_within_hour' => 'boolean', 'vitamin_k_given' => 'boolean'];

    /** @return BelongsTo<MchDelivery, $this> */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(MchDelivery::class, 'delivery_id');
    }

    /** @return BelongsTo<Patient, $this> */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'child_patient_id');
    }
}
