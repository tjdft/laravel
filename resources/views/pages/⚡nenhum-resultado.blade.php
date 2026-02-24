<?php

use Livewire\Component;

new class extends Component {
    public ?string $titulo = null;
};
?>

<div class="mx-auto opacity-50 max-w-96 text-center my-16">
    <x-icon name="lucide.wind" class="w-10 h-10" />
    <div class="text-lg">
        {{ $titulo ?? 'Nenhum resultado encontrado.'}}
    </div>
</div>
