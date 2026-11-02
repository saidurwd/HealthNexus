<?php

namespace App\Models\Facility;

use App\Models\Concerns\HasPublicId;
use App\Models\User;
use Database\Factories\Facility\FacCleaningTaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacCleaningTask extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): FacCleaningTaskFactory
    {
        return FacCleaningTaskFactory::new();
    }

    protected $table = 'fac_cleaning_tasks';

    protected $guarded = ['id'];

    protected $casts = ['requested_at' => 'datetime', 'started_at' => 'datetime', 'done_at' => 'datetime', 'inspected_at' => 'datetime', 'checklist' => 'array', 'bed_released' => 'boolean'];

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
