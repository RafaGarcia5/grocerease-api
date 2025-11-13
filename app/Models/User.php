<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'address',
        'payment',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'email_verified_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'address' => 'array'
    ];

    // Relationships
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }
}
