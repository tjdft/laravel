<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Component;
use TJDFT\Laravel\Models\Permission;
use TJDFT\Laravel\Traits\WithPaginationAndReset;

new class extends Component {
    use WithPaginationAndReset;

    // Busca por nome ou matrícula
    public string $search = '';

    public ?string $permission_id = null;

    public bool $filtros = false;

    public function mount(): void
    {
        auth()->user()->authorize("permissoes.gerenciar");
    }

    // Lista de usuários
    public function users(): LengthAwarePaginator
    {
        /** @var Model $model */
        $model = config('auth.providers.users.model');

        return $model::query()
            ->with('grant')
            ->when(is_numeric($this->search), fn(Builder $query) => $query->where('matricula', $this->search))
            ->when(! is_numeric($this->search), fn(Builder $query) => $query->searchAny(['nome'], $this->search))
            ->when($this->permission_id, fn(Builder $query) => $query->whereRelation('grant', 'permissions', 'like', "%{$this->permission_id}%"))
            ->orderBy('nome')
            ->paginate(10);
    }

    public function permissions(): Collection
    {
        return Permission::orderBy('description')->get();
    }

    public function headers(): array
    {
        return [
            ['key' => 'nome', 'label' => 'Pessoa'],
        ];
    }

    public function breadcrumbs(): array
    {
        return [
            ['link' => '/', 'icon' => 's-home'],
            ['label' => ''],
        ];
    }

    public function with(): array
    {
        return [
            'users' => $this->users(),
            'headers' => $this->headers(),
            'breadcrumbs' => $this->breadcrumbs(),
            'permissions' => $this->permissions(),
        ];
    }
}; ?>

<div>
    {{-- CABEÇALHO --}}
    <x-header title="Permissões" separator progress-indicator>
        <x-slot:subtitle>
            <x-breadcrumbs :items="$breadcrumbs" />
        </x-slot:subtitle>
        <x-slot:actions>
            <x-input placeholder="Nome ou matrícula ..." wire:model.live.debounce="search" icon="lucide.search" clearable />
            <div class="divider divider-horizontal mx-0"></div>
            <x-button icon="lucide.filter" wire:click="$toggle('filtros')" />
        </x-slot:actions>
    </x-header>

    {{-- USUÁRIOS --}}
    <x-card shadow>
        <x-table
            :headers="$headers"
            :rows="$users"
            with-pagination
            link="/auth/permissions/{id}"
            show-empty-text
            empty-text="Nenhum resultado encontrado."
            class="arrows"
        >
            @scope('cell_nome', $user)
            <x-list-item :item="$user" value="nome" sub-value="matricula" avatar="foto" fallback-avatar="/user.png" no-separator no-hover class="!-mx-5 !py-0" />
            @endscope
        </x-table>
    </x-card>

    {{--  FILTROS  --}}
    <x-drawer title="Filtros" wire:model="filtros" separator with-close-button right class="w-full lg:w-4/12">
        <div class="grid gap-5">
            <x-input label="Pessoa" placeholder="Nome ou matrícula ..." wire:model.live.debounce="search" icon="lucide.search" clearable />
            <x-select label="Permission" wire:model.live="permission_id" :options="$permissions" option-value="name" option-label="description" placeholder="Selecione" />
        </div>

        <x-slot:actions>
            <x-button label="Limpar" wire:click="clear" spinner />
            <x-button label="Pronto" @click="$wire.filtros = false" icon="lucide.check" class="btn-primary" />
        </x-slot:actions>
    </x-drawer>
</div>
