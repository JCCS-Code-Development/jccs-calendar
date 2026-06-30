<?php

namespace App\Models;

use App\Models\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->role?->name === $role;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('Admin');
    }

    public function isOffice(): bool
    {
        return $this->hasRole('Office');
    }

    public function isLead(): bool
    {
        return $this->hasRole('Lead');
    }

    public function isCrew(): bool
    {
        return $this->hasRole('Crew');
    }

    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    public function canManageEvents(): bool
    {
        return $this->isAdmin() || $this->isOffice();
    }

    public function canViewAllEvents(): bool
    {
        return $this->isAdmin() || $this->isOffice();
    }
}
