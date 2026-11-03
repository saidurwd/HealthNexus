<?php

namespace App\Models\Facility;

use Database\Factories\Facility\FacVisitorBlockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacVisitorBlock extends Model
{
    use HasFactory;

    protected static function newFactory(): FacVisitorBlockFactory
    {
        return FacVisitorBlockFactory::new();
    }

    protected $table = 'fac_visitor_blocks';

    protected $guarded = ['id'];

    protected $casts = ['active' => 'boolean'];
}
