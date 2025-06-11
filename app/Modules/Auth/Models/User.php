<?php

namespace App\Modules\Auth\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'users';
    
    protected array $fillable = [
        'name',
        'email',
        'password',
        'role',
        'active',
        'email_verified_at',
        'remember_token',
        'last_login'
    ];
    
    protected array $hidden = [
        'password',
        'remember_token'
    ];
    
    protected array $casts = [
        'active' => 'boolean',
        'email_verified_at' => 'datetime',
        'last_login' => 'datetime'
    ];
    
    public function articles()
    {
        return $this->hasMany(\App\Modules\Articles\Models\Article::class, 'user_id');
    }
    
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
    
    public function isActive(): bool
    {
        return $this->active === true;
    }
    
    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }
}