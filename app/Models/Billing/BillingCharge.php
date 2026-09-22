<?php

namespace App\Models\Billing;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BillingCharge extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'branch_id', 'patient_id', 'encounter_id', 'billing_item_id', 'department_id', 'provider_id', 'source_type', 'source_id', 'idempotency_key', 'status', 'quantity', 'currency', 'unit_price', 'gross_amount', 'discount_type', 'discount_amount', 'tax_amount', 'net_amount', 'charged_at', 'billed_at', 'cancelled_at', 'cancelled_by', 'cancellation_reason', 'created_by'];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'unit_price' => 'decimal:2', 'gross_amount' => 'decimal:2', 'discount_amount' => 'decimal:2', 'tax_amount' => 'decimal:2', 'net_amount' => 'decimal:2', 'charged_at' => 'datetime', 'billed_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function patient(): BelongsTo { return $this->belongsTo(\App\Models\Patient::class); }
    public function encounter(): BelongsTo { return $this->belongsTo(\App\Models\Encounter::class); }
    public function billingItem(): BelongsTo { return $this->belongsTo(BillingItem::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function provider(): BelongsTo { return $this->belongsTo(User::class, 'provider_id'); }
    public function source(): MorphTo { return $this->morphTo(); }
    public function cancelledBy(): BelongsTo { return $this->belongsTo(User::class, 'cancelled_by'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function invoiceItem(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(BillingInvoiceItem::class, 'charge_id'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }

    public function isBilled(): bool { return $this->billed_at !== null; }
    public function isCancelled(): bool { return $this->cancelled_at !== null; }
}
