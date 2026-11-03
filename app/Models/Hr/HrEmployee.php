<?php

namespace App\Models\Hr;

use App\Models\Concerns\HasPublicId;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrEmployee extends Model
{
    use HasFactory, HasPublicId;

    protected $table = 'hr_employees';

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['date_of_birth' => 'date:Y-m-d', 'joined_on' => 'date:Y-m-d', 'probation_ends_on' => 'date:Y-m-d', 'confirmed_on' => 'date:Y-m-d', 'separated_on' => 'date:Y-m-d', 'national_id' => 'encrypted', 'tin' => 'encrypted', 'bank_account' => 'encrypted', 'mobile_wallet' => 'encrypted'];

    protected $hidden = ['national_id', 'tin', 'bank_account', 'mobile_wallet'];

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /** @return BelongsTo<HrJob, $this> */
    public function job(): BelongsTo
    {
        return $this->belongsTo(HrJob::class, 'job_id');
    }

    /** @return BelongsTo<HrPosition, $this> */
    public function position(): BelongsTo
    {
        return $this->belongsTo(HrPosition::class, 'position_id');
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /** @return BelongsTo<HrEmployee, $this> */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'manager_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<HrCredential, $this> */
    public function credentials(): HasMany
    {
        return $this->hasMany(HrCredential::class, 'employee_id');
    }

    /** @return HasMany<HrContract, $this> */
    public function contracts(): HasMany
    {
        return $this->hasMany(HrContract::class, 'employee_id');
    }

    /** @return HasMany<HrEmployeeHistory, $this> */
    public function history(): HasMany
    {
        return $this->hasMany(HrEmployeeHistory::class, 'employee_id');
    }

    /** @return HasMany<HrChecklist, $this> */
    public function checklists(): HasMany
    {
        return $this->hasMany(HrChecklist::class, 'employee_id');
    }

    /** @return HasMany<HrDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(HrDocument::class, 'employee_id');
    }
}
