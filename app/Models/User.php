<?php

declare(strict_types = 1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Contracts\Auditable;

class User extends Authenticatable implements Auditable, FilamentUser, HasTenants
{
    use \OwenIt\Auditing\Auditable;
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;
    use HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'is_owner',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_owner' => 'boolean',
        ];
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class);
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isOwner(): bool
    {
        return $this->is_owner;
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->stores;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->stores()->whereKey($tenant)->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
