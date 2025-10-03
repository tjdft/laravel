<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use TJDFT\Laravel\Models\Permission;

new class extends Component {
    use Toast;

    // Usuário em edição
    public User $user;

    // Busca por nome da permissão (TODAS)
    public string $search_all_permission = '';

    // Busca por nome da permissão (SELECIONADAS)
    public string $search_selected_permission = '';

    // Busca por usuário para copiar perfil
    public string $search_user = '';

    // Usuário selecionado para copiar perfil
    #[Validate('required', as: 'Usuário')]
    public ?int $user_id = null;

    // Permissões selecionadas
    public array $selection = [];

    // Controla exibição do modal de copiar perfil
    public bool $showModal = false;

    // Resultado da busca de usuários para copiar perfil
    public Collection $users;

    public function mount(): void
    {
        auth()->user()->authorize("permissoes.gerenciar");

        $this->selection = $this->user->permissions()->pluck('name')->all();
        $this->search();
    }

    // Permissões selecionadas
    public function selected_permissions(): Collection
    {
        return Permission::query()
            ->whereIn('name', $this->selection)
            ->when($this->search_selected_permission, function (Builder $query) {
                $value = str($this->search_selected_permission)->replace(' ', '%');

                if ($value->isNotEmpty()) {
                    $query->where(function (Builder $query) use ($value) {
                        $query->whereRaw("immutable_unaccent(name::text) ILIKE immutable_unaccent(?::text)", ["%{$value}%"]);
                        $query->orWhereRaw("immutable_unaccent(description::text) ILIKE immutable_unaccent(?::text)", ["%{$value}%"]);
                    });
                }
            })
            ->orderBy('name')
            ->get();
    }

    // Busca as permissões disponíveis
    public function all_permissions(): Collection
    {
        return Permission::query()
            ->when($this->search_all_permission, function (Builder $query) {
                $value = str($this->search_all_permission)->replace(' ', '%');

                if ($value->isNotEmpty()) {
                    $query->orWhereRaw("immutable_unaccent(name::text) ILIKE immutable_unaccent(?::text)", ["%{$value}%"]);
                    $query->orWhereRaw("immutable_unaccent(description::text) ILIKE immutable_unaccent(?::text)", ["%{$value}%"]);
                }
            })
            ->orderBy('name')
            ->get();
    }

    // Busca usuários para copiar perfil
    public function search(string $value = ''): void
    {
        $selecionado = User::where('id', $this->user_id)->get();

        $this->users = User::query()
            ->searchAny(['nome', 'matricula'], $value)
            ->take(10)
            ->orderBy('nome')
            ->get()
            ->merge($selecionado);
    }

    // Copia o perfil de outro usuário
    public function copiar(): void
    {
        $this->validate();

        $this->selection = User::find($this->user_id)->permissions()->pluck('name')->all();

        $this->salvar();

        $this->showModal = false;
    }

    // Seleciona todas as permissões
    public function todos(): void
    {
        $this->selection = Permission::all()->pluck('name')->all();
    }

    // Desmarca todas as permissões
    public function nenhum(): void
    {
        $this->selection = [];
    }

    // Remove uma permissão da seleção
    public function remover(string $permission): void
    {
        $this->selection = array_values(array_diff($this->selection, [$permission]));
    }

    // Salva as permissões selecionadas
    public function salvar(): void
    {
        $this->user->syncPermissions($this->selection);

        $this->success('Permissões atualizadas.');
    }

    public function breadcrumbs(): array
    {
        return [
            ['link' => '/', 'icon' => 's-home'],
            ['label' => 'Permissões', 'link' => '/auth/permissions'],
        ];
    }

    public function with(): array
    {
        return [
            'all_permissions' => $this->all_permissions(),
            'selected_permissions' => $this->selected_permissions(),
            'breadcrumbs' => $this->breadcrumbs(),
        ];
    }
}; ?>

<div>
    {{-- CABEÇALHO --}}
    <x-header title="Permissões" separator progress-indicator>
        <x-slot:subtitle>
            <x-breadcrumbs :items="$this->breadcrumbs()" />
        </x-slot:subtitle>
        <x-slot:actions>
            <x-button label="Copiar perfil" wire:click="$toggle('showModal')" icon="lucide.clipboard-copy" tooltip-bottom="Copiar permissões de outro usuário" />
            <div class="divider divider-horizontal"></div>
            <x-button label="Salvar" wire:click="salvar" class="btn-primary justify-self-start" icon="lucide.check" spinner />
        </x-slot:actions>
    </x-header>

    <div class="grid gap-8">
        {{-- USUÁRIO  --}}
        <x-card shadow>
            <x-list-item :item="$user" value="nome" sub-value="matricula" avatar="foto" fallback-avatar="/imagens/empty-user.jpg" no-separator no-hover class="-my-3" />
        </x-card>

        <div class="grid grid-cols-2 gap-8">
            {{-- PERMISSÕES DISPONÍVEIS  --}}
            <x-card title="Opções" shadow separator>
                <x-slot:menu>
                    @if($all_permissions->count() > $selected_permissions->count())
                        <x-button label="Selecionar todos" wire:click="todos" class="btn-sm" spinner />
                    @else
                        <x-button label="Desmarcar todos" wire:click="nenhum" class="btn-sm text-error" spinner />
                    @endif
                </x-slot:menu>

                <x-input
                    placeholder="Pesquisar ..."
                    wire:model.live.debounce="search_all_permission"
                    icon="lucide.search"
                    @input="$nextTick(() => $refs.allPermissons.scrollTo({ top: -100, behavior: 'smooth'}))"
                    clearable />

                <div class="overflow-y-scroll h-[calc(100vh-400px)] mt-3" x-ref="allPermissons">
                    @foreach($all_permissions as $permission)
                        <x-list-item :item="$permission" value="description" sub-value="name" class="text-sm">
                            <x-slot:avatar>
                                <x-checkbox
                                    :sub-label="$permission->name"
                                    wire:model.live="selection"
                                    :value="$permission->name"
                                    class="checkbox-sm"
                                />
                            </x-slot:avatar>
                        </x-list-item>
                    @endforeach
                </div>
            </x-card>

            {{-- PERMISSÕES SELECIONADAS  --}}
            <x-card title="Selecionados" shadow separator>

                <x-input
                    placeholder="Pesquisar ..."
                    wire:model.live.debounce="search_selected_permission"
                    icon="lucide.search"
                    @input="$nextTick(() => $refs.selectedPermissions.scrollTo({ top: -100, behavior: 'smooth'}))"
                    clearable />

                <div class="overflow-y-scroll h-[calc(100vh-400px)] mt-3" x-ref="selectedPermissions">
                    @foreach($selected_permissions as $permission)
                        <x-list-item :item="$permission" value="description" sub-value="name" class="text-sm">
                            <x-slot:avatar>
                                <x-icon name="lucide.shield-check" class="bg-neutral/5 p-2 rounded-full w-9 h-9" />
                            </x-slot:avatar>
                            <x-slot:actions>
                                <x-button
                                    icon="lucide.trash"
                                    wire:click="remover('{{ $permission->name }}')"
                                    class="text-error btn-ghost btn-sm btn-circle"
                                    spinner="remover('{{ $permission->name }}')"
                                />
                            </x-slot:actions>
                        </x-list-item>
                    @endforeach
                </div>
            </x-card>
        </div>
    </div>

    {{-- MODAL - COPIAR PERFIL --}}
    <x-modal wire:model="showModal" title="Copiar perfil" box-class="overflow-y-visible" separator>
        <x-choices
            wire:model="user_id"
            label="Origem"
            :options="$users"
            option-label="nome"
            option-sub-label="matricula"
            option-avatar="foto"
            icon="lucide.search"
            searchable
            single
            placeholder="Nome ou matrícula..."
            hint="Selecione um usuário para obter o perfil" />

        <div class="text-xs font-bold mt-5 mb-1">Copiar para</div>
        <div class="text-sm">{{ $user->nome }}</div>

        <x-slot:actions>
            <x-button
                label="Copiar perfil"
                wire:click="copiar"
                wire:confirm="As permissões serão aplicadas imediatamente ao usuário."
                icon="lucide.check"
                spinner
                class="btn-primary" />
        </x-slot:actions>
    </x-modal>
</div>
