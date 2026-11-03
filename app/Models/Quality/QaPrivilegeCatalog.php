<?php

namespace App\Models\Quality;

use Database\Factories\Quality\QaPrivilegeCatalogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QaPrivilegeCatalog extends Model
{
    use HasFactory;

    protected static function newFactory(): QaPrivilegeCatalogFactory
    {
        return QaPrivilegeCatalogFactory::new();
    }

    protected $table = 'qa_privilege_catalog';

    protected $guarded = ['id'];

    protected $casts = ['active' => 'boolean'];
}
