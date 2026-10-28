<?php

namespace App\Models\Hr;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrVacancy extends Model
{
    use HasPublicId;

    protected $table = 'hr_vacancies';

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['opened_on' => 'date:Y-m-d', 'closes_on' => 'date:Y-m-d'];

    /** @return HasMany<HrCandidate, $this> */
    public function candidates(): HasMany
    {
        return $this->hasMany(HrCandidate::class, 'vacancy_id');
    }
}
