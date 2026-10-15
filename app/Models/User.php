<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['name', 'email', 'password', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const STATUS_ACTIVE = 'activo';

    public const STATUS_INACTIVE = 'inactivo';

    /** @use HasFactory<UserFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_access_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    public function permissions(): Builder
    {
        return Permission::query()
            ->whereIn('permissions.id', function ($query): void {
                $query->select('permission_role.permission_id')
                    ->from('permission_role')
                    ->join('role_user', 'role_user.role_id', '=', 'permission_role.role_id')
                    ->where('role_user.user_id', $this->getKey());
            })
            ->distinct();
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles()->where('roles.slug', $slug)->exists();
    }

    public function hasPermission(string $slug): bool
    {
        return $this->permissions()->where('permissions.slug', $slug)->exists();
    }

    public function events(): HasMany
    {
        return $this->hasMany(Evento::class, 'usuario_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'usuario_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
