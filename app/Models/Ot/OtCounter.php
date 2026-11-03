<?php

namespace App\Models\Ot;

use Illuminate\Database\Eloquent\Model;

class OtCounter extends Model
{
    protected $fillable = ['organization_id', 'branch_id', 'document_type', 'prefix', 'last_number'];
}
