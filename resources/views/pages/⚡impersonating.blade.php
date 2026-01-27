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
        <div class="flex justify-between items-center bg-warning p-1 text-xs">
            <div>
                <x-mary-icon name="lucide.shield-check" class="w-4 me-1 mb-0.5" />
                Personificando <b>{{ auth()->user()->nome }}</b>
            </div>
            <div>
                <x-button label="Sair da personificação" wire:click="unpersonate" icon="lucide.power" class="btn-xs btn-ghost" spinner />
            </div>
        </div>
    @endif
</div>
