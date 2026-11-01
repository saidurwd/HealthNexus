<?php

namespace App\Models\Quality;

use App\Models\Concerns\HasPublicId;
use App\Models\User;
use Database\Factories\Quality\QaComplianceItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaComplianceItem extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): QaComplianceItemFactory
    {
        return QaComplianceItemFactory::new();
    }

    protected $table = 'qa_compliance_items';

    protected $guarded = ['id'];

    protected $casts = ['due_on' => 'date:Y-m-d', 'last_done_on' => 'date:Y-m-d', 'alerted_at' => 'datetime'];

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
