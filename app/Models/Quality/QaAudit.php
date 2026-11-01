<?php

namespace App\Models\Quality;

use App\Models\Concerns\HasPublicId;
use App\Models\User;
use Database\Factories\Quality\QaAuditFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QaAudit extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): QaAuditFactory
    {
        return QaAuditFactory::new();
    }

    protected $table = 'qa_audits';

    protected $guarded = ['id'];

    protected $casts = ['audited_on' => 'date:Y-m-d', 'compliance' => 'decimal:2'];

    /** @return HasMany<QaAuditFinding, $this> */
    public function findings(): HasMany
    {
        return $this->hasMany(QaAuditFinding::class, 'audit_id');
    }

    /** @return BelongsTo<User, $this> */
    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }
}
