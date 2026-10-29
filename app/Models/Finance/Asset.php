<?php

namespace App\Models\Finance;

use App\Models\Concerns\HasPublicId;
use Database\Factories\Finance\AssetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): AssetFactory
    {
        return AssetFactory::new();
    }

    protected $table = 'fin_assets';

    protected $guarded = ['id'];

    protected $casts = ['acquired_on' => 'date:Y-m-d', 'depreciation_from' => 'date:Y-m-d', 'disposed_on' => 'date:Y-m-d', 'cost' => 'decimal:2', 'salvage_value' => 'decimal:2', 'accumulated' => 'decimal:2'];
}
