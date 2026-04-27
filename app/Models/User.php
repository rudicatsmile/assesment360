<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'password',
        'role',
        'role_id',
        'department',
        'department_id',
        'is_active',
        'time_limit_minutes',
        'filling_started_at',
        'slug',
        'prosentase',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'time_limit_minutes' => 'integer',
        'filling_started_at' => 'datetime',
    ];

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    public function createdQuestionnaires(): HasMany
    {
        return $this->hasMany(Questionnaire::class, 'created_by');
    }

    public function departmentRef(): BelongsTo
    {
        return $this->belongsTo(Departement::class, 'department_id');
    }

    public function roleRef(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function roleSlug(): string
    {
        return (string) ($this->roleRef?->slug ?: $this->role);
    }

    public function hasAnyRoleSlug(array $slugs): bool
    {
        return in_array($this->roleSlug(), $slugs, true);
    }

    public function isAdminRole(): bool
    {
        return $this->hasAnyRoleSlug((array) config('rbac.admin_slugs', []));
    }

    public function isEvaluatorRole(): bool
    {
        $configuredEvaluatorSlugs = (array) config('rbac.evaluator_slugs', []);
        if ($configuredEvaluatorSlugs !== [] && $this->hasAnyRoleSlug($configuredEvaluatorSlugs)) {
            return true;
        }

        // Fallback for custom role catalogs: any non-admin role is treated as evaluator.
        if ($this->role_id !== null) {
            return !$this->isAdminRole();
        }

        return false;
    }

    public function canManageRoles(): bool
    {
        return $this->isAdminRole();
    }
}
