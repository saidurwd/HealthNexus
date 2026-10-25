<?php

namespace App\Models\Ot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtTheatreCapability extends Model
{
    protected $fillable = ['theatre_id', 'capability'];

    public function theatre(): BelongsTo
    {
        return $this->belongsTo(OtTheatre::class, 'theatre_id');
    }
}
