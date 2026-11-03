<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationModule extends Model
{
    protected $fillable = ['organization_id', 'hospital_id', 'module_key', 'is_enabled', 'changed_by'];

    protected $casts = ['is_enabled' => 'boolean'];
}
