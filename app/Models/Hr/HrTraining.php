<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrTraining extends Model
{
    protected $table = 'hr_trainings';

    protected $guarded = ['id'];

    protected $casts = ['held_on' => 'date:Y-m-d', 'mandatory' => 'boolean'];

    /** @return HasMany<HrTrainingRecord, $this> */
    public function records(): HasMany
    {
        return $this->hasMany(HrTrainingRecord::class, 'training_id');
    }
}
