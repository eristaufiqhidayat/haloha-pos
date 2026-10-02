<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = ['name', 'email', 'password', 'role_id', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_active' => 'boolean', 'email_verified_at' => 'datetime'];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function hasPermission(string $code): bool
    {
        if (! $this->is_active || ! $this->role?->is_active) {
            return false;
        }
        [$module,$action] = array_pad(explode('.', $code, 2), 2, '');
        if (! isset(config('pos.modules')[$module]['actions'][$action])) {
            return false;
        }

        return $this->role->is_system || $this->role->permissions->contains('code', $code);
    }
}
