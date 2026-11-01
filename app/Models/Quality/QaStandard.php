<?php

namespace App\Models\Quality;

use App\Models\Concerns\HasPublicId;
use Database\Factories\Quality\QaStandardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QaStandard extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): QaStandardFactory
    {
        return QaStandardFactory::new();
    }

    protected $table = 'qa_standards';

    protected $guarded = ['id'];

    protected $casts = ['active' => 'boolean'];

    /** @return HasMany<QaStandardItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(QaStandardItem::class, 'standard_id');
    }
}
