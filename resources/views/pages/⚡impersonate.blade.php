<?php

use App\Models\User;
use Livewire\Component;
use TJDFT\Laravel\Services\PessoasPolvoService;

new class extends Component {
    public ?string $login = null;

    public function mount(): void
    {
        auth()->user()->authorize('impersonate');
    }

    public function impersonate(): void
    {
        try {
            auth()->user()->impersonate($this->login);
            $this->redirect('/');
        } catch (Throwable $e) {
            $this->resetErrorBag();
            $this->addError('login', $e->getMessage());
        }
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
            'breadcrumbs' => $this->breadcrumbs(),
        ];
    }
};
?>

<div>
    <x-header title="Personificar" separator progress-indicator>
        <x-slot:subtitle>
            <x-breadcrumbs :items="$breadcrumbs" />
        </x-slot:subtitle>
    </x-header>

    <div class="grid lg:grid-cols-12 gap-8 items-start">
        <div class="col-span-12 lg:col-span-4">
            <x-input wire:model="login" wire:keydown.enter="impersonate" placeholder="Informe o login" icon="lucide.shield-check" hint="Quem você quer personificar?">
                <x-slot:append>
                    <x-button icon="lucide.arrow-right" class="join-item btn-primary" wire:click="impersonate" spinner />
                </x-slot:append>
            </x-input>

            <div class="grid gap-5 mt-8 text-xs">
                <div>
                    A personificação funciona por <b>login</b>, pois ele é único. Diferente da <b>matrícula</b>, que pode repetir-se.
                </div>
                <div>
                    Alguns requisitados não podem ser personificados. Pois, não possuem login dentro do padrão do RH.
                </div>
            </div>
        </div>
        <div class="col-span-12 lg:col-span-8">
            <img src="/impersonate.png?v=2" class="mx-auto w-full max-w-96" aria-label="Pessoa observando algumas bandeiras." />
        </div>
    </div>
</div>
