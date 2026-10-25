<?php

namespace App\Models\Icu;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcuProgressNoteAmendment extends Model
{
    public const TYPE_ADDENDUM = 'addendum';

    public const TYPE_CORRECTION = 'correction';

    protected $fillable = ['note_id', 'amendment_type', 'reason', 'content', 'created_by'];

    public function note(): BelongsTo { return $this->belongsTo(IcuProgressNote::class, 'note_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
