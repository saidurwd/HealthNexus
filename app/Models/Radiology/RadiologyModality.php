<?php

namespace App\Models\Radiology;

use App\Models\AppointmentRoom;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RadiologyModality extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'section_id', 'department_id', 'room_id',
        'code', 'name', 'modality_type', 'manufacturer', 'model', 'serial_number',
        'ae_title', 'ip_address', 'port', 'pacs_endpoint', 'location', 'status', 'is_active',
    ];

    protected function casts(): array
    {
        return ['port' => 'integer', 'is_active' => 'boolean'];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function section(): BelongsTo { return $this->belongsTo(RadiologySection::class, 'section_id'); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function room(): BelongsTo { return $this->belongsTo(AppointmentRoom::class, 'room_id'); }
    public function examinations(): HasMany { return $this->hasMany(RadiologyExamination::class, 'modality_id'); }

    public function isOnline(): bool { return $this->status === 'online'; }
    public function isBookable(): bool { return $this->is_active && in_array($this->status, ['online', 'offline'], true); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
