<?php

namespace App\Models\Ipd;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpdProviderAssignment extends Model
{
    public const ROLE_ADMITTING = 'admitting';
    public const ROLE_ATTENDING = 'attending';
    public const ROLE_CONSULTING = 'consulting';
    public const ROLE_ON_CALL = 'on_call';

    protected $fillable = ['admission_id', 'provider_id', 'role', 'assigned_at', 'ended_at', 'assigned_by'];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime', 'ended_at' => 'datetime'];
    }

    public function admission(): BelongsTo { return $this->belongsTo(IpdAdmission::class, 'admission_id'); }
    public function provider(): BelongsTo { return $this->belongsTo(Provider::class, 'provider_id'); }
    public function assignedBy(): BelongsTo { return $this->belongsTo(User::class, 'assigned_by'); }
}
