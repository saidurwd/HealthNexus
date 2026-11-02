<?php

namespace App\Models\Ai;

use App\Models\Concerns\HasPublicId;
use App\Models\User;
use Database\Factories\Ai\AiUseCaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUseCase extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): AiUseCaseFactory
    {
        return AiUseCaseFactory::new();
    }

    protected $table = 'ai_use_cases';

    protected $guarded = ['id'];

    protected $casts = ['implemented' => 'boolean', 'approved_at' => 'datetime'];

    /** @return BelongsTo<AiProvider, $this> */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'provider_id');
    }

    /** @return BelongsTo<AiModel, $this> */
    public function model(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'model_id');
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
