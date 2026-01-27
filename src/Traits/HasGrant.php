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
        return Role::whereIn('name', $this->grant->roles ?? [])->get();
    }

    public function permissions(): Collection
    {
        return Permission::whereIn('name', $this->grant->permissions ?? [])->get();
    }

    public function assignRole(string $role): void
    {
        $role = Role::where('name', $role)->firstOrFail();

        $grant = $this->grant()->firstOrNew();

        $grant->roles = $grant->roles?->merge($role->name) ?? collect([$role->name]);
        $grant->permissions = $grant->permissions?->merge($role->permissions)->unique() ?? $role->permissions;
        $grant->save();
    }

    public function unassignRole(string $role): void
    {
        $role = Role::where('name', $role)->firstOrFail();

        $grant = $this->grant()->firstOrNew();

        $grant->roles = $grant->roles?->filter(fn($r) => $r !== $role->name) ?? collect();
        $grant->permissions = $grant->permissions?->filter(fn($perm) => ! $role->permissions->contains($perm)) ?? collect();
        $grant->save();
    }

    public function givePermissionTo(Collection|array|string $permissions): void
    {
        $grant = $this->grant()->firstOrNew();

        $permissions = is_array($permissions) || is_string($permissions) ? collect($permissions) : collect($permissions)->pluck('name');

        $grant->permissions = $grant->permissions?->add($permissions)->filter()->unique() ?? $permissions;
        $grant->save();
    }

    public function revokePermissionTo(string $permission): void
    {
        $grant = $this->grant()->firstOrNew();

        $grant->permissions = $grant->permissions?->filter(fn($perm) => $perm !== $permission) ?? collect();
        $grant->save();
    }

    public function syncPermissions(Collection|array $permissions): void
    {
        $permissions = is_array($permissions) ? collect($permissions) : collect($permissions)->pluck('name');

        $grant = $this->grant()->firstOrNew();
        $grant->permissions = $permissions;
        $grant->save();
    }

    public function syncRoles(array $roles): void
    {
        $rolesCollection = collect($roles);

        $permissions = Role::whereIn('name', $rolesCollection)->get()->flatMap->permissions->unique();

        $grant = $this->grant()->firstOrNew();
        $grant->roles = $rolesCollection;
        $grant->permissions = $permissions;
        $grant->save();
    }

    public function can($abilities, $arguments = []): bool
    {
        return $this->grant?->permissions?->contains($abilities) ?? false;
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

    // Possui a role específica
    public function hasRole(string $role): bool
    {
        return $this->grant?->roles?->contains($role) ?? false;
    }

    // Possui qualquer uma das roles informadas
    public function hasAnyRole(array $roles): bool
    {
        return $this->grant?->roles?->intersect($roles)->isNotEmpty() ?? false;
    }

    // Possui a permission específica
    public function hasPermission(string $permission): bool
    {
        return $this->grant?->permissions?->contains($permission) ?? false;
    }

    // Possui qualquer uma das permissions informadas
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->grant?->permissions?->intersect($permissions)->isNotEmpty() ?? false;
    }

    // Verifica se o usuário é administrador
    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->hasPermission('permissions.gerenciar');
    }
}
