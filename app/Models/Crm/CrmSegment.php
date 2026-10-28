<?php

namespace App\Models\Crm;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmSegment extends Model
{
    use HasFactory, HasPublicId;

    protected $table = 'crm_segments';

    protected $guarded = ['id'];

    protected $casts = ['criteria' => 'array', 'approved_at' => 'datetime', 'last_counted_at' => 'datetime'];
}
