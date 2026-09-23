<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderUnavailability extends Model
{
    use HasFactory;

    // "Unavailability" is uncountable — Eloquent's auto-pluralization would otherwise guess
    // provider_unavailabilities, which doesn't match the migration's table name.
    protected $table = 'provider_unavailability';

    protected $fillable = [
        'company_id',
        'branch_id',
        'provider_id',
        'reason_type',
        'start_at',
        'end_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function overlaps(\Carbon\Carbon $start, \Carbon\Carbon $end): bool
    {
        return $this->start_at < $end && $this->end_at > $start;
    }
}
