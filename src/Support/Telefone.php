<?php

namespace TJDFT\Laravel\Support;

class Telefone
{
    public static function formatado(?string $telefone = null, ?string $placeholder = null): ?string
    {
        if (! $telefone) {
            return $placeholder;
        }

        $digits = preg_replace('/\D/', '', $telefone);

        return match (true) {
            strlen($digits) === 11 => preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $digits),
            strlen($digits) === 10 => preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $digits),
            strlen($digits) === 9 => preg_replace('/(\d{5})(\d{4})/', '$1-$2', $digits),
            strlen($digits) === 8 => preg_replace('/(\d{4})(\d{4})/', '$1-$2', $digits),
            default => $digits,
        };
    }
}
