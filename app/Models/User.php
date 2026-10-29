<?php

namespace App\Models;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property ?string $email
 * @property ?string $phone
 * @property bool $is_active
 * @property ?Carbon $last_login_at
 */
#[Fillable(['name', 'email', 'phone', 'password', 'is_active', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * The hiker profile of a member account (bookings and points).
     *
     * @return HasOne<Hiker, $this>
     */
    public function hiker(): HasOne
    {
        return $this->hasOne(Hiker::class);
    }

    public function canAccessAdmin(): bool
    {
        return $this->is_active && $this->can(Permission::AccessAdmin->value);
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(array_map(fn (Role $r) => $r->value, Role::staff()));
    }

    public function primaryRole(): ?Role
    {
        $name = $this->getRoleNames()->first();

        return $name ? Role::tryFrom($name) : null;
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeStaff(Builder $query): void
    {
        $query->role(array_map(fn (Role $r) => $r->value, Role::staff()));
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $query->when($term, fn (Builder $q) => $q->where(fn (Builder $q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%")));
    }

    public static function activeSuperAdminCount(): int
    {
        return static::role(Role::SuperAdmin->value)->where('is_active', true)->count();
    }
}
