<?php

namespace App\Models\Ot;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtOperativeNoteAmendment extends Model
{
    protected $fillable = ['operative_note_id', 'amendment_type', 'reason', 'content', 'created_by'];

    public function operativeNote(): BelongsTo
    {
        return $this->belongsTo(OtOperativeNote::class, 'operative_note_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
