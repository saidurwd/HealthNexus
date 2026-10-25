<?php

namespace App\Models\Ot;

use App\Models\Concerns\HasPublicId;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtSurgicalTeam extends Model
{
    use HasPublicId;

    public const ROLE_PRIMARY_SURGEON = 'primary_surgeon';

    public const ROLE_ASSISTANT_SURGEON = 'assistant_surgeon';

    public const ROLE_ANESTHETIST = 'anesthetist';

    public const ROLE_ANESTHESIA_ASSISTANT = 'anesthesia_assistant';

    public const ROLE_SCRUB_NURSE = 'scrub_nurse';

    public const ROLE_CIRCULATING_NURSE = 'circulating_nurse';

    public const ROLE_TECHNICIAN = 'technician';

    public const ROLE_OTHER = 'other';

    protected $table = 'ot_surgical_team';

    protected $fillable = ['surgery_id', 'provider_id', 'role', 'assigned_at', 'removed_at', 'reason', 'assigned_by'];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime', 'removed_at' => 'datetime'];
    }

    public function surgery(): BelongsTo
    {
        return $this->belongsTo(OtSurgery::class, 'surgery_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
