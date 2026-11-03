<?php

namespace App\Models\Ai;

use App\Models\Concerns\HasPublicId;
use Database\Factories\Ai\AiProviderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiProvider extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): AiProviderFactory
    {
        return AiProviderFactory::new();
    }

    protected $table = 'ai_providers';

    protected $guarded = ['id'];

    protected $casts = ['allows_identifiable' => 'boolean', 'legal_approved_at' => 'datetime'];

    /** @return HasMany<AiModel, $this> */
    public function models(): HasMany
    {
        return $this->hasMany(AiModel::class, 'provider_id');
    }
}
