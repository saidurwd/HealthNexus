<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'phone', 'avatar', 'profile_picture', 'timezone', 'locale', 'is_active', 'failed_login_attempts', 'locked_until'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    public const MAX_FAILED_LOGIN_ATTEMPTS = 5;

    public const LOCKOUT_MINUTES = 15;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'failed_login_attempts' => 'integer',
            'locked_until' => 'datetime',
        ];
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'user_companies')
            ->withPivot('access_level', 'is_default', 'metadata')
            ->withTimestamps();
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'user_branches')
            ->withPivot('access_level', 'is_default', 'metadata')
            ->withTimestamps();
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'user_departments')
            ->withPivot('access_level', 'metadata')
            ->withTimestamps();
    }

    public function companyAccess(): HasMany
    {
        return $this->hasMany(UserCompany::class);
    }

    public function branchAccess(): HasMany
    {
        return $this->hasMany(UserBranch::class);
    }

    public function departmentAccess(): HasMany
    {
        return $this->hasMany(UserDepartment::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function getProfilePictureUrlAttribute(): string
    {
        if ($this->profile_picture) {
            return asset('storage/'.$this->profile_picture);
        }

        if ($this->avatar) {
            return $this->avatar;
        }

        return asset('vendor/adminlte/dist/assets/img/default-150x150.png');
    }

    public function adminlte_image(): string
    {
        return $this->profile_picture_url;
    }

    public function doctorSchedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class, 'doctor_id');
    }

    public function appointmentSlots(): HasMany
    {
        return $this->hasMany(AppointmentSlot::class, 'doctor_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class, 'doctor_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'doctor_id');
    }

    public function investigationOrders(): HasMany
    {
        return $this->hasMany(InvestigationOrder::class, 'doctor_id');
    }
}
