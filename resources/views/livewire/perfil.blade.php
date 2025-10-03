<?php

use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;

new class extends Component {
    public function mount(): void
    {
    }

    // Lista de usuários
    public function users(): Collection
    {
        return User::query()
            ->where('cpf', auth()->user()->cpf)
            ->get();
    }

    // Iniciar sessão
    public function iniciar(int $matricula)
    {
        $cpf = auth()->user()->cpf;

        // Logout
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        // Login para matricula selecionada
        $user = User::where('cpf', $cpf)->where('matricula', $matricula)->firstOrFail();

        auth()->login($user);

        // Invoca action para ajuste de permissões
        try {
            $action = app()->make(config('tjdft.permissions_action'));
            new $action($user)->execute();
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
        <div class="text-center  opacity-50 border-b border-b-base-content/40 border-dashed  pb-5 text-sm">
            <x-icon name="lucide.users" class="w-12 h-12" />
            <div class="font-bold mt-2">Perfil de Acesso</div>
            <div class="mt-3">
                Você pode alternar o perfil clicando em
                <x-icon name="lucide.settings" class="w-4 h-4" />
                no menu principal
            </div>
        </div>

        <div>
            @foreach($users as $user)
                <x-list-item :item="$user" value="nome" sub-value="matricula" avatar="foto" fallback-avatar="/imagens/empty-user.jpg">
                    <x-slot:actions>
                        <x-button
                            label="Iniciar sessão"
                            wire:click="iniciar('{{ $user->matricula}}')"
                            icon-right="lucide.arrow-right"
                            class="btn-primary btn-sm"
                            spinner="iniciar('{{ $user->matricula}}')" />
                    </x-slot:actions>
                </x-list-item>
            @endforeach
        </div>
    </div>

</div>
