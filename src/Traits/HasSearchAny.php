<?php

namespace TJDFT\Laravel\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Faz uma busca em múltiplas colunas, ignorando acentos e sensitive (ILIKE).
 *
 *  $query->searchAny(["coluna1", "coluna2"], "maria")
 */
trait HasSearchAny
{
    public function scopeSearchAny(Builder $query, array $columns, string $value): Builder
    {
        return $query->where(function (Builder $query) use ($columns, $value) {
            $schema = config('tjdft.pgsql_extensions.schema');
            $schema_dot = $schema ? "$schema." : '';

            foreach ($columns as $column) {
                // Se é uma coluna JSON transforma para a sintaxe correta do postgres
                if (str($column)->contains('->')) {
                    $column = collect(explode('->', $column))
                        ->map(fn($segment, $index) => $index === 0 ? "\"{$segment}\"" : "'{$segment}'")
                        ->join('->');

                    $column = str($column)->replaceLast('->', '->>');
                }

                // Quebra string para o ILIKE
                $value = str($value)->replace(' ', '%');

                // Apenas aplica condição se o valor não estiver vazio (desempenho)
                if ($value->isNotEmpty()) {
                    // Ignora acentos
                    $query->orWhereRaw("{$schema_dot}immutable_unaccent({$column}::text) ILIKE {$schema_dot}immutable_unaccent(?::text)", ["%{$value}%"]);
                }
            }
        });
    }
}
