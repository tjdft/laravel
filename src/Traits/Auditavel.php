<?php

namespace TJDFT\Laravel\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Models\Audit;

trait Auditavel
{
    use Auditable;

    // Configuração de exibição das auditorias.
    public function transformAudit(array $data): array
    {
        collect($this->auditoria())->each(function ($settings, $field) use (&$data) {
            if (! Arr::has($data, "new_values.{$field}")) {
                return;
            }

            if (! isset($settings['transform'])) {
                return;
            }

            $modelClass = $settings['transform'];

            $data['old_values'][$field] = $modelClass::find($this->getOriginal($field))->nome ?? null;
            $data['new_values'][$field] = $modelClass::find($this->getAttribute($field))->nome ?? null;
        });

        return $data;
    }

    // Transforma os dados de auditoria para exibir o "nome do tipo" ao invés do ID.

    public function auditorias(): Collection
    {
        $auditorias = $this->audits()->with(['user'])->take(config('tjdft.auditoria.maximo_exibicao'))->latest()->get();

        return $auditorias->map(function (Audit $auditoria) {
            $modified = $auditoria->getModified();
            $modificados = [];

            collect($modified)->each(function ($values, $field) use (&$modificados) {
                // Label do campo
                $label = $this->auditoria()[$field] ?? $field;
                $label = is_array($label) ? $label['nome'] : $label;

                // Valores antigos e novos
                $value_old = data_get($values, "old");
                $value_new = data_get($values, "new");

                // Se for objeto ou JSON, limpa
                $value_old = ! isset($this->auditoria()[$field]['campos']) ? $value_old : null;
                $value_new = ! isset($this->auditoria()[$field]['campos']) ? $value_new : null;

                // Trata múltiplos campos para colunas JSON
                collect($this->auditoria()[$field]['campos'] ?? [])->each(function ($campo) use ($values, &$value_old, &$value_new) {
                    $old = data_get($values, "old.{$campo}");
                    $new = data_get($values, "new.{$campo}");

                    $value_old = $value_old ? str($value_old)->append(" / {$old}")->toString() : $old;
                    $value_new = $value_new ? str($value_new)->append(" / {$new}")->toString() : $new;
                });

                // Campo boolean
                if (gettype($value_old) === 'boolean' || gettype($value_new) === 'boolean') {
                    $value_old = $value_old ? 'Sim' : 'Não';
                    $value_new = $value_new ? 'Sim' : 'Não';
                }

                $modificados[$label] = [
                    'old' => $value_old,
                    'new' => $value_new
                ];
            });

            $auditoria->modificados = $modificados;

            return $auditoria;
        });
    }

    // Total de auditorias
    protected function auditoriasCount(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->audits()->count(),
        );
    }

    // Configuração dos campos
    protected function auditoria(): array
    {
        return [];
    }
}
