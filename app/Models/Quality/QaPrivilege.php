<?php

namespace App\Models\Quality;

use App\Models\Concerns\HasPublicId;
use App\Models\Hr\HrEmployee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaPrivilege extends Model
{
    use HasPublicId;

    protected $table = 'qa_privileges';

    protected $guarded = ['id'];

    protected $casts = ['decided_at' => 'datetime', 'granted_on' => 'date:Y-m-d', 'expires_on' => 'date:Y-m-d'];

    /** @return BelongsTo<QaPrivilegeCatalog, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(QaPrivilegeCatalog::class, 'catalog_id');
    }

    /** @return BelongsTo<HrEmployee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }
}
