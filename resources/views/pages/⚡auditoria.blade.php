<?php

use App\Models\Pagina;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Component;

new class extends Component {
    public bool $show = false;

    public Model $model;

    public function auditorias(): Collection
    {
        return $this->model->auditorias();
    }

    public function with(): array
    {
        return [
            'auditorias' => $this->show ? $this->auditorias() : collect()
        ];
    }
};
?>

<div {{ $attributes->class([]) }}>
    <x-button label="Auditoria" wire:click="$toggle('show')" icon="lucide.activity" class="btn-outline btn-primary" responsive />

    <x-drawer wire:model="show" title="Auditoria" right separator close-on-escape with-close-button class="w-11/12">
        <div class="ml-5">
            @forelse($auditorias as $auditoria)
                <x-timeline-item
                    :title="$auditoria->created_at->format('d/m/Y')"
                    subtitle="{{ $auditoria->user->primeiroNome }} ({{ $auditoria->user->login }}) modificou em {{ $auditoria->created_at->format('d/m/Y H:i') }}"
                    :pending="!$loop->first"
                    :first="$loop->first"
                    :icon="$loop->first ? 'lucide.clock' : 'lucide.clock-fading'"
                >
                    <x-slot:description>
                        <div class="bg-base-200/50 px-5 rounded mb-10 border border-base-content/10">
                            @foreach($auditoria->modificados as $campo => $valor)
                                <div class="grid gap-3 not-last:border-b border-b-base-content/10 py-5">
                                    <div class="font-bold text-base-content text-xs">
                                        {{ $campo }}
                                    </div>
                                    <div>
                                        @if(gettype($valor['old'] ?? null) == 'object' || gettype($valor['new'] ?? null) == 'object')
                                            <x-diff
                                                :old="json_encode($valor['old'] ?? '[vazio]', JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)"
                                                :new="json_encode($valor['new'] ?? '[vazio]', JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)"
                                                file-name="diff.json"
                                            />
                                        @elseif (str($valor['old'] ?? '')->length() < 50 || str($valor['new'] ?? '')->length() < 50)
                                            <div>
                                                <span class="text-base-content/30">
                                                    {!! $valor['old'] ?? '[vazio]' !!}
                                                </span>
                                                ⇢
                                                {!! $valor['new'] ?? '[vazio]' !!}
                                            </div>
                                        @else
                                            <div x-data="{ expanded_old: false, expanded_new: false }">
                                                <x-button
                                                    label="Ver antigo"
                                                    @click="expanded_old = ! expanded_old; expanded_new = false"
                                                    class="btn-xs mb-1"
                                                    ::class="!expanded_old || 'btn-neutral'"
                                                />
                                                ⇢
                                                <x-button
                                                    label="Ver novo"
                                                    @click="expanded_new = ! expanded_new; expanded_old = false"
                                                    class="btn-xs mb-1"
                                                    ::class="!expanded_new || 'btn-neutral'" />

                                                <div x-show="expanded_old" class="border border-dashed p-5 rounded mt-8 bg-base-100 mb-5">
                                                    {!! $valor['old'] ?? '[vazio]' !!}
                                                </div>

                                                <div x-show="expanded_new" class="border border-dashed p-5 rounded mt-8 bg-base-100 mb-5">
                                                    {!! $valor['new'] ?? '[vazio]' !!}
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-slot:description>
                </x-timeline-item>
            @empty
                <div wire:loading>
                    <x-loading />
                </div>

                <div wire:loading.remove>
                    <livewire:tjdft::nenhum-resultado titulo="Nenhuma modificação." />
                </div>
            @endforelse

            @if($model->auditoriasCount > config('tjdft.auditoria.maximo_exibicao'))
                <div class="mt-5 opacity-50">
                    Exibindo os {{ config('tjdft.auditoria.maximo_exibicao')  }} registros mais recentes de um total de {{ $model->auditoriasCount }} modificações.
                </div>
            @endif
        </div>

        <x-slot:actions>
            <x-button label="Fechar" wire:click="$toggle('show')" />
        </x-slot:actions>
    </x-drawer>
</div>
