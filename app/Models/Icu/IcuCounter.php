<?php

namespace App\Models\Icu;

use Illuminate\Database\Eloquent\Model;

class IcuCounter extends Model
{
    protected $fillable = ['organization_id', 'branch_id', 'document_type', 'prefix', 'last_number'];
}
