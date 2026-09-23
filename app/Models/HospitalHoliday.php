<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HospitalHoliday extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'department_id',
        'name',
        'date',
        'is_recurring_annually',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
        'is_recurring_annually' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * True if this holiday record applies to the given date — either an exact match, or (for a
     * recurring holiday) the same month/day in any year.
     */
    public function appliesTo(\Carbon\Carbon $date): bool
    {
        if ($this->is_recurring_annually) {
            return $this->date->format('m-d') === $date->format('m-d');
        }

        return $this->date->isSameDay($date);
    }
}
