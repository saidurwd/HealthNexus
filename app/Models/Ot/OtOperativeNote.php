<?php

namespace App\Models\Ot;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OtOperativeNote extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_FINAL = 'final';

    protected $fillable = [
        'surgery_id', 'preoperative_diagnosis', 'postoperative_diagnosis', 'anesthesia_type',
        'position', 'findings', 'technique_summary', 'estimated_blood_loss', 'status',
        'documented_by', 'finalized_by', 'finalized_at',
    ];

    protected function casts(): array
    {
        return ['finalized_at' => 'datetime'];
    }

    public function surgery(): BelongsTo
    {
        return $this->belongsTo(OtSurgery::class, 'surgery_id');
    }

    public function documentedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'documented_by');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function amendments(): HasMany
    {
        return $this->hasMany(OtOperativeNoteAmendment::class, 'operative_note_id');
    }
}
