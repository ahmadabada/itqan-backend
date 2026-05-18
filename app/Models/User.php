<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['national_id', 'first_name', 'second_name', 'third_name', 'family_name', 'phone', 'gender', 'password_hash', 'role', 'is_active', 'fcm_token', 'fcm_platform', 'last_mobile_login_at'])]
#[Hidden(['password_hash', 'remember_token'])]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function getAuthIdentifierName(): string
    {
        return 'national_id';
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin;
    }

    public function fullName(): string
    {
        return implode(' ', array_filter([
            $this->first_name,
            $this->second_name,
            $this->third_name,
            $this->family_name,
        ]));
    }

    public function shortName(): string
    {
        return trim($this->first_name . ' ' . ($this->family_name ?? ''));
    }

    protected function casts(): array
    {
        return [
            'role'                  => UserRole::class,
            'gender'                => Gender::class,
            'is_super_admin'        => 'boolean',
            'is_active'             => 'boolean',
            'last_login_at'         => 'datetime',
            'last_mobile_login_at'  => 'datetime',
            'password_hash'         => 'hashed',
        ];
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class, 'examiner_id');
    }

    public function grantedPermits(): HasMany
    {
        return $this->hasMany(ReexamPermit::class, 'granted_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function createdStudents(): HasMany
    {
        return $this->hasMany(Student::class, 'created_by_user_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class, 'last_user_id');
    }
}
