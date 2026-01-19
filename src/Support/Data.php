<?php

namespace TJDFT\Laravel\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

class Data
{
    public static function formatada(Carbon|CarbonImmutable|string|null $data = null, ?string $placeholder = null): ?string
    {
        if (! $data) {
            return $placeholder;
        }

        return Carbon::parse($data)->format('d/m/Y');
    }
}
