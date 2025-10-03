<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Volt\Component;
use TJDFT\Laravel\Traits\WithPaginationAndReset;

new class extends Component {
    use WithPaginationAndReset;

    // Busca por nome ou matrícula
    public string $search = '';

    public function mount(): void
    {
        auth()->user()->authorize("permissoes.gerenciar");
    }

    // Lista de usuários
    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->with('grant')
            ->when(is_numeric($this->search), fn(Builder $query) => $query->where('matricula', $this->search))
            ->when(! is_numeric($this->search), fn(Builder $query) => $query->searchAny(['nome'], $this->search))
            ->paginate();
    }

    // Cabeçalhos da tabela
    public function headers(): array
    {
        return [
            ['key' => 'nome', 'label' => 'Pessoa', 'class' => 'w-96'],
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
            class="arrows">
            @scope('cell_nome', $user)
            <x-list-item :item="$user" value="nome" sub-value="matricula" avatar="foto" fallback-avatar="/imagens/empty-user.jpg" no-separator no-hover class="-mx-4 -my-3">
                <x-slot:actions class="text-xs opacity-50">
                    {{ $user->roles()->pluck('description')->join(', ') ?: '-' }}
                </x-slot:actions>
            </x-list-item>
            @endscope
        </x-table>
    </x-card>
</div>
