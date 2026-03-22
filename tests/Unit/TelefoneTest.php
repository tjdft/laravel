<?php

use TJDFT\Laravel\Support\Telefone;

test('Formata telefone com DDD', function () {
    expect(Telefone::formatado("11988776655"))->toBe("(11) 98877-6655");
});

test('Formata telefone sem DDD', function () {
    expect(Telefone::formatado("988776655"))->toBe("98877-6655");
});

test('Usa placeholder para telefones vazios', function () {
    expect(Telefone::formatado(null, "sem telefone"))->toBe("sem telefone");
});

test('Telefones vazios retornam `null` se não for passado placeholder', function () {
    expect(Telefone::formatado(null))->toBeNull();
});
