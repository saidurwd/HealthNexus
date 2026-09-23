<?php

namespace App\Models\Pharmacy;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PharmacyMedication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'generic_id', 'brand_id', 'dosage_form_id', 'route_id',
        'code', 'name', 'strength', 'strength_unit', 'pack_size', 'dispensing_unit',
        'prescription_unit', 'manufacturer', 'is_prescription_required', 'is_controlled',
        'is_high_alert', 'storage_temperature_min', 'storage_temperature_max',
        'temperature_sensitive', 'is_active', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_prescription_required' => 'boolean',
            'is_controlled' => 'boolean',
            'is_high_alert' => 'boolean',
            'temperature_sensitive' => 'boolean',
            'is_active' => 'boolean',
            'pack_size' => 'integer',
            'storage_temperature_min' => 'decimal:2',
            'storage_temperature_max' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function generic(): BelongsTo { return $this->belongsTo(PharmacyGeneric::class, 'generic_id'); }
    public function brand(): BelongsTo { return $this->belongsTo(PharmacyBrand::class, 'brand_id'); }
    public function dosageForm(): BelongsTo { return $this->belongsTo(PharmacyDosageForm::class, 'dosage_form_id'); }
    public function route(): BelongsTo { return $this->belongsTo(PharmacyRoute::class, 'route_id'); }
    public function ingredients(): HasMany { return $this->hasMany(PharmacyMedicationIngredient::class, 'medication_id'); }
    public function storeLevels(): HasMany { return $this->hasMany(PharmacyMedicationStoreLevel::class, 'medication_id'); }
    public function batches(): HasMany { return $this->hasMany(PharmacyBatch::class, 'medication_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
