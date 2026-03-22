<?php

namespace TJDFT\Laravel\Casts;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Trata a conversão do atributo `competencia` para o formato `Carbon`.
 *
 * No banco de dados é um `integer`, ex: `202507`
 * Ao recuperar converte para um objeto Carbon representando o primeiro dia do mês `2025-07-01`.
 */
class CompetenciaCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?CarbonImmutable
    {
        return $value ? CarbonImmutable::createFromFormat('Ym', (string) $value)->startOfMonth()->copy() : null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return ($value instanceof CarbonImmutable || $value instanceof Carbon) ? $value->format('Ym') : $value;
    }
}
