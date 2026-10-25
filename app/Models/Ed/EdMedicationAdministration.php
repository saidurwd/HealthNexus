<?php

namespace App\Models\Ed;

use App\Models\Concerns\HasPublicId;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EdMedicationAdministration extends Model
{
    use HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['given_at' => 'datetime', 'is_verbal_order' => 'boolean'];

    /** @return BelongsTo<User, $this> */
    public function giver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'given_by');
    }

    /** @return BelongsTo<User, $this> */
    public function orderer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }
}
