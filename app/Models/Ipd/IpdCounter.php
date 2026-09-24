<?php

namespace App\Models\Ipd;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpdCounter extends Model
{
    protected $fillable = ['company_id', 'branch_id', 'document_type', 'prefix', 'last_number'];

    protected function casts(): array
    {
        return ['last_number' => 'integer'];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
}
