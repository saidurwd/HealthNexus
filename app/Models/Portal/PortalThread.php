<?php

namespace App\Models\Portal;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PortalThread extends Model
{
    use HasPublicId;

    protected $table = 'portal_threads';

    protected $guarded = ['id'];

    protected $casts = ['urgent' => 'boolean', 'reply_due_at' => 'datetime', 'last_message_at' => 'datetime', 'closed_at' => 'datetime'];

    /** @return HasMany<PortalMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(PortalMessage::class, 'thread_id');
    }

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
