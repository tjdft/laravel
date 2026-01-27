<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Component;

new class extends Component {
    /** @var Model $model */
    private $model;

    public function boot(): void
    {
        $this->model = config('auth.providers.users.model');
    }

    // Lista de usuários
    public function users(): Collection
    {
        // Somente aqueles com o mesmo CPF do usuário autenticado
        return $this->model::query()
            ->where('cpf', auth()->user()->cpf)
            ->get();
    }

    // Iniciar sessão
    public function iniciar(int $matricula)
    {
        $cpf = auth()->user()->cpf;

        // Logout
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        // Login para matricula selecionada
        $user = $this->model::where('cpf', $cpf)->where('matricula', $matricula)->firstOrFail();

        auth()->login($user);

        // Invoca action para ajuste de permissões
        try {
            app()->make(config('tjdft.permissions_action'))->execute();
        } catch (Throwable $e) {
            // Sumprime erro caso a classe não exista
        }

        return redirect('/');
    }

    public function with(): array
    {
        return [
            'users' => $this->users(),
        ];
    }
}; ?>

<div>
    <div class="grid gap-8 max-w-[600px] mx-auto mt-20">
        <div class="text-center opacity-50 border-b border-b-base-content/40 border-dashed  pb-5 text-sm">
            <x-mary-icon name="lucide.users" class="w-12 h-12" />
            <div class="font-bold mt-2">Selecione um perfil de acesso</div>
            <div class="mt-3">
                Você encontra esta página novamente clicando no ícone
                <x-mary-icon name="lucide.settings" class="w-4 h-4" />
                do painel principal
            </div>
        </div>

        <div class="truncate min-h-screen">
            @foreach($users as $user)
                <x-list-item :item="$user" value="nome" avatar="foto" fallback-avatar="/user.png">
                    <x-slot:subValue>
                        {{ $user['matricula'] }} / {{ $user['rh_tipo'] }} / {{ $user['rh_status'] }}
                    </x-slot:subValue>
                    <x-slot:actions>
                        <x-button
                            label="Iniciar sessão"
                            wire:click="iniciar('{{ $user->matricula}}')"
                            icon-right="lucide.arrow-right"
                            class="btn-primary btn-sm"
                            spinner="iniciar('{{ $user->matricula}}')"
                            responsive
                        />
                    </x-slot:actions>
                </x-list-item>
            @endforeach
        </div>
    </div>

</div>
