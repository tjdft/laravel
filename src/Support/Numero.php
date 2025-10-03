<?php

namespace TJDFT\Laravel\Support;

use Illuminate\Support\Number;

class Numero
{
    // Não faz arredondamento, simplesmente corta as casas decimais
    public static function truncado(?float $valor = null, int $casas = 2): float
    {
        return $valor ? bcdiv($valor, 1, $casas) : 0;
    }

    // Formata percentual para Humanos
    public static function percentual(float|int $valor, int $precisao = 2): string
    {
        return Number::percentage($valor * 100, precision: $precisao);
    }

    // Formata número para padrão brasileiro
    public static function formatado(float|int $valor): string
    {
        return Number::format($valor ?? 0, precision: 2);
    }

    // Formata moeda para Humanos
    public static function moeda(float|int $valor): string
    {
        return Number::currency($valor ?? 0, precision: 2);
    }

    // Formata CNPJ
    public static function cnpj(string $cnpj): string
    {
        // Remove tudo que não é dígito
        $cnpj = preg_replace('/\D/', '', $cnpj);

        // Retorna o CNPJ original se não tiver 14 dígitos
        if (strlen($cnpj) !== 14) {
            return $cnpj;
        }

        return preg_replace(
            '/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/',
            '$1.$2.$3/$4-$5',
            $cnpj
        );
    }

    // Formata CPF
    public static function cpf(string $cpf): string
    {
        // Remove tudo que não é dígito
        $cpf = preg_replace('/\D/', '', $cpf);
        // Retorna o CPF original se não tiver 11 dígitos
        if (strlen($cpf) !== 11) {
            return $cpf;
        }

        return preg_replace(
            '/(\d{3})(\d{3})(\d{3})(\d{2})/',
            '$1.$2.$3-$4',
            $cpf
        );
    }
}
