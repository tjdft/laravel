<?php

namespace TJDFT\Laravel\Traits;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use TJDFT\Laravel\Models\Grant;
use TJDFT\Laravel\Models\Permission;
use TJDFT\Laravel\Models\Role;

trait HasGrant
{
    public function grant(): HasOne
    {
        return $this->hasOne(Grant::class);
    }

    public function roles(): Collection
    {
        return Role::whereIn('name', $this->grant->roles)->get();
    }

    public function permissions(): Collection
    {
        return Permission::whereIn('name', $this->grant->permissions)->get();
    }

    public function assignRole(string $role): void
    {
        $role = Role::where('name', $role)->firstOrFail();

        $grant = $this->grant()->firstOrNew();

        $grant->roles = $grant->roles?->merge($role->name) ?? collect([$role->name]);
        $grant->permissions = $grant->permissions?->merge($role->permissions)->unique() ?? $role->permissions;
        $grant->save();
    }

    public function revokePermissionTo(string $permission): void
    {
        $this->grant()->update([
            $grant->permissions?->filter(fn($perm) => $perm !== $permission) ?? collect()
        ]);
    }

    public function givePermissionTo(string $permission): void
    {
        $grant = $this->grant()->firstOrNew();

        $grant->permissions = $grant->permissions?->add($permission)->unique() ?? collect([$permission]);
        $grant->save();
    }

    public function syncPermissions(Collection|array $permissions): void
    {
        $permissions = is_array($permissions) ? collect($permissions) : collect($permissions)->pluck('name');

        $this->grant->update(['permissions' => $permissions]);
    }

    public function can($abilities, $arguments = []): bool
    {
        return $this->grant->permissions?->contains($abilities);
    }

    public function cannot($abilities, $arguments = []): bool
    {
        return ! $this->can($abilities);
    }

    public function authorize(string $permission): void
    {
        if ($this->cannot($permission)) {
            abort(403);
        }
    }
}
