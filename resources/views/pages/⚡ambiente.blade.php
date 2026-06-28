<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div>
    @if(!app()->isProduction())
        <div class="flex bg-error text-white p-5 text-sm rounded fixed bottom-5 left-5 z-10">
            <x-icon name="lucide.shell" class="font-bold  me-2" label="Você está no ambiente de testes" />
            <x-badge class="badge-sm ms-3" :value="config('app.env')" />
        </div>
    @endif
</div>

