<?php

namespace TJDFT\Laravel\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grant extends Model
{
    protected $guarded = ['id'];

    public function getTable()
    {
        return config('tjdft.acl.tables.grants');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'roles' => AsCollection::class,
            'permissions' => AsCollection::class,
        ];
    }
}
