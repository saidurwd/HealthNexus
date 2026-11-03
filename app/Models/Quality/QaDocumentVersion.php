<?php

namespace App\Models\Quality;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaDocumentVersion extends Model
{
    protected $table = 'qa_document_versions';

    protected $guarded = ['id'];

    protected $casts = ['submitted_at' => 'datetime', 'approved_at' => 'datetime', 'effective_on' => 'date:Y-m-d', 'review_due_on' => 'date:Y-m-d'];

    protected static function booted(): void
    {
        static::updating(function (self $v) {
            if (in_array($v->getOriginal('status'), ['approved', 'superseded'], true) && $v->isDirty(['body', 'version', 'document_id'])) {
                throw new \LogicException(__('An approved version cannot be changed: make a new version.'));
            }
        });
    }

    /** @return BelongsTo<QaDocument, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(QaDocument::class, 'document_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
