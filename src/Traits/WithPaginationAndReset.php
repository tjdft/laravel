<?php

namespace TJDFT\Laravel\Traits;

use Livewire\WithPagination;

/**
 * Reseta paginação quando qualquer propriedade do componente é alterada.
 * Adicionalmente inclui o helper $this->clear() para resetar manualmente.
 */
trait WithPaginationAndReset
{
    use WithPagination;

    public function updated(mixed $property): void
    {
        $isExpandingOrSelecting = str($property)->contains('expanded') || str($property)->contains('selection');

        if ($isExpandingOrSelecting) {
            return;
        }

        $this->resetPage();
    }

    public function clear(): void
    {
        // Propriedades nativas do Livewire
        $this->reset();

        // Resetar paginação
        $this->resetPage();
    }
}
