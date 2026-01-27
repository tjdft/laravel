<?php

use Carbon\Carbon;
use TJDFT\Laravel\Support\Data;

test('Formata data para o padrão brasileiro - STRING', function () {
    expect(Data::formatada("2025-04-12"))->toBe("12/04/2025");
});

test('Formata data para o padrão brasileiro - CARBON', function () {
    $data = new Carbon("2023-11-05");

    expect(Data::formatada($data))->toBe("05/11/2023");
});

test('Usa placeholder para datas vazias', function () {
    expect(Data::formatada(null, "sem data"))->toBe("sem data");
});

test('Datas vazias retornam `null` se não for passado placeholder', function () {
    expect(Data::formatada(null))->toBeNull();
});
