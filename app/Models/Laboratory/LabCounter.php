<?php

namespace App\Models\Laboratory;

use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabCounter extends Model
{
    protected $fillable = ['organization_id', 'branch_id', 'document_type', 'prefix', 'last_number'];

    protected function casts(): array
    {
        return ['last_number' => 'integer'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
