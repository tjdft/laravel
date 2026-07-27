<?php

namespace TJDFT\Laravel\Traits;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use TJDFT\Laravel\Models\Grant;
use TJDFT\Laravel\Models\Permission;

trait HasGrant
{
    public function grant(): HasOne
    {
        return $this->hasOne(Grant::class);
    }

    public function permissions(): Collection
    {
        return Permission::whereIn('name', $this->grant->permissions ?? [])->get();
    }

    public function givePermissionTo(Collection|array|string $permissions): void
    {
        $grant = $this->grant()->firstOrNew();

        $permissions = is_array($permissions) || is_string($permissions) ? collect($permissions)->all() : collect($permissions)->pluck('name')->all();

        $grant->permissions = $grant->permissions?->merge($permissions)->filter()->unique()->sort()->values()->all() ?? collect($permissions)->sort()->values()->all();

        $grant->save();
    }

    public function revokePermissionTo(string $permission): void
    {
        $grant = $this->grant()->firstOrNew();

        $grant->permissions = $grant->permissions?->filter(fn($perm) => $perm !== $permission)->values() ?? collect();
        $grant->save();
    }

    public function syncPermissions(Collection|array $permissions): void
    {
        $permissions = is_array($permissions) ? collect($permissions)->values() : collect($permissions)->pluck('name')->values();

        $grant = $this->grant()->firstOrNew();
        $grant->permissions = $permissions->sort()->values()->all();
        $grant->save();
    }

    public function can($abilities, $arguments = []): bool
    {
        if (app()->runningInConsole()){
            return true;
        }

        if (str($abilities)->contains('*')) {
            $abilities = str($abilities)->replace('*', '')->toString();
        }

        return $this->grant?->permissions?->contains(fn(string $item) => str($item)->contains($abilities)) ?? false;
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

    // Possui a permission específica
    public function hasPermission(string $permission): bool
    {
        return $this->can($permission);
    }

    // Possui qualquer uma das permissions informadas
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->grant?->permissions?->intersect($permissions)->isNotEmpty() ?? false;
    }

    // Verifica se o usuário é administrador
    public function isAdmin(): bool
    {
        return $this->hasPermission('permissoes.gerenciar');
    }

    #[Scope]
    public function admin(Builder $query): Builder
    {
        return $query->whereHas('grant', fn($query) => $query->whereJsonContains('permissions', 'permissoes.gerenciar'));
    }
}

