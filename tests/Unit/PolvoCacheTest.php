<?php

use Illuminate\Support\Facades\Http;
use TJDFT\Laravel\Services\PessoasPolvoService;

beforeEach(function () {
    // Fake da resposta do Polvo
    Http::fake(['/graphql' => ['teste']]);
});

test('Desabilita o cache da query graphQL', function () {
    // Dado que existe uma query graphQL
    $service = new PessoasPolvoService();

    // Quando que for executada sem cache
    $service->semCache()->porCpf('345');

    // Então nada é colocado em cache
    expect($service->cache()->get())->toBeNull();
});

test('Cache personalizado da query graphQL', function () {
    // Dado que existe uma query graphQL
    $service = new PessoasPolvoService();

    // Quando for executada com TTL e KEY personalizados
    $service->lembrar('10 seconds', 'servidor-345')->porCpf('345');

    // Então o TTL e KEY devem ser respeitados
    expect($service->getKey())->toBe('servidor-345');
    expect($service->getTtl())->equalTo(now()->parse('10 seconds'));
});

test('Expiração de cache da query graphQL', function () {
    // Dado que existe uma query graphQL
    $service = new PessoasPolvoService();

    // Quando for executada com cache
    $service->porCpf('345');

    $isValido = $service->isValido();
    $isExpirado = $service->isExpirado();

    // Dado que avançei no tempo em duas horas
    now()->setTestNow(now()->addHours(2));

    // Então o cache está expirado
    expect($isExpirado)->toBeTrue();

    // Então não possui cache válido
    expect($isValido)->toBeTrue();

    // Então a chave não existe mais em cache
    expect($service->cache()->get())->toBeNull();
});

