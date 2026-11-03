<?php

namespace App\Models\Quality;

use App\Models\Concerns\HasPublicId;
use App\Models\User;
use Database\Factories\Quality\QaDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QaDocument extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): QaDocumentFactory
    {
        return QaDocumentFactory::new();
    }

    protected $table = 'qa_documents';

    protected $guarded = ['id'];

    /** @return HasMany<QaDocumentVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(QaDocumentVersion::class, 'document_id');
    }

    /** @return BelongsTo<QaDocumentVersion, $this> */
    public function current(): BelongsTo
    {
        return $this->belongsTo(QaDocumentVersion::class, 'current_version_id');
    }

    /** @return HasMany<QaDocumentAssignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(QaDocumentAssignment::class, 'document_id');
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
