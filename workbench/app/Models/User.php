<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use TJDFT\Laravel\Casts\CompetenciaCast;
use TJDFT\Laravel\Casts\NumeroCast;
use TJDFT\Laravel\Contracts\AuditavelContract;
use TJDFT\Laravel\Traits\Auditavel;
use TJDFT\Laravel\Traits\HasGrant;
use TJDFT\Laravel\Traits\HasImpersonate;
use TJDFT\Laravel\Traits\HasSearchAny;
use Workbench\Database\Factories\UserFactory;

class User extends Authenticatable implements AuditavelContract
{
    use HasGrant, HasImpersonate, HasSearchAny, HasFactory, Notifiable, Auditavel;

    protected $guarded = ["id"];

    protected static function newFactory()
    {
        return UserFactory::new();
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(UserType::class, 'type_id');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'cpf' => NumeroCast::class,
            'competencia' => CompetenciaCast::class,
            'password' => 'hashed',
            'localizacao' => 'object',
        ];
    }

    protected function auditoria(): array
    {
        return [
            'type_id' => [
                'nome' => 'Tipo de usuário',
                'transform' => UserType::class,
            ],
            'localizacao' => [
                'nome' => 'Localização',
                'campos' => ['sigla', 'codigo']
            ]
        ];
    }
}
