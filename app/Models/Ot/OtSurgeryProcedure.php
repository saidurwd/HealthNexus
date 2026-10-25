<?php

namespace App\Models\Ot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtSurgeryProcedure extends Model
{
    public const ROLE_PRIMARY = 'primary';

    public const ROLE_SECONDARY = 'secondary';

    public const ROLE_ADDITIONAL = 'additional';

    public const ROLE_UNPLANNED = 'unplanned';

    public const ROLE_ABORTED = 'aborted';

    protected $fillable = ['surgery_id', 'procedure_id', 'role', 'sequence', 'notes'];

    public function surgery(): BelongsTo
    {
        return $this->belongsTo(OtSurgery::class, 'surgery_id');
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(OtProcedure::class, 'procedure_id');
    }
}
