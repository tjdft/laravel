<?php

namespace TJDFT\Laravel\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Role extends Model
{
    protected $guarded = ['id'];

    public function getTable()
    {
        return config('tjdft.acl.tables.roles');
    }

    public function givePermissionTo(Collection|array $permissions): void
    {
        $permissions = is_array($permissions) ? collect($permissions) : collect($permissions)->pluck('name');

        $permissions->merge($this->permissions);

        $this->update(['permissions' => $permissions]);
    }

    protected function casts(): array
    {
        return [
            'permissions' => AsCollection::class,
        ];
    }
}
