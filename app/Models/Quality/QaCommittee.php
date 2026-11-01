<?php

namespace App\Models\Quality;

use App\Models\Concerns\HasPublicId;
use App\Models\User;
use Database\Factories\Quality\QaCommitteeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QaCommittee extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): QaCommitteeFactory
    {
        return QaCommitteeFactory::new();
    }

    protected $table = 'qa_committees';

    protected $guarded = ['id'];

    protected $casts = ['active' => 'boolean'];

    /** @return HasMany<QaCommitteeMember, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(QaCommitteeMember::class, 'committee_id');
    }

    /** @return HasMany<QaMeeting, $this> */
    public function meetings(): HasMany
    {
        return $this->hasMany(QaMeeting::class, 'committee_id');
    }

    /** @return BelongsTo<User, $this> */
    public function chair(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chair_id');
    }
}
