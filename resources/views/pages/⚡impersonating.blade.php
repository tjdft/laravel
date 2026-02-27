<?php

use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Component;

new class extends Component {
    public function unpersonate(): void
    {
        auth()->user()->unpersonate();
        $this->redirect('/');
    }
}; ?>

<div>
    @if(auth()->user()->impersonating())
        <div class="bg-warning p-1 text-xs">
            <div class="max-w-screen-2xl w-full mx-auto flex justify-between items-center">
                <div>
                    <x-mary-icon name="lucide.drama" class="w-4 me-1 mb-0.5" />
                    Personificando
                    <b>
                        {{ auth()->user()->primeiroNome }}
                        ({{ auth()->user()->login }})
                    </b>
                </div>
                <div>
                    <x-button label="Sair da personificação" wire:click="unpersonate" icon="lucide.power" class="btn-xs btn-ghost" spinner responsive />
                </div>
            </div>
        </div>
    @endif
</div>
