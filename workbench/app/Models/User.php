<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use TJDFT\Laravel\Traits\HasGrant;
use TJDFT\Laravel\Traits\HasImpersonate;
use TJDFT\Laravel\Traits\HasSearchAny;
use Workbench\Database\Factories\UserFactory;

class User extends Authenticatable
{
    use HasGrant, HasImpersonate, HasSearchAny, HasFactory, Notifiable;

    protected $guarded = ["id"];

    protected static function newFactory()
    {
        return UserFactory::new();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'localizacao' => 'object',
        ];
    }
}
