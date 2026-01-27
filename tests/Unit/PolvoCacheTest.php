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

test('Usa o cache da query graphQL previamente executada', function () {
    // Dado que existe uma query graphQL
    $service1 = new PessoasPolvoService();
    $service1->porCpf('345');

    // Dado que existe outra instância do serviço
    $service2 = new PessoasPolvoService();
    $service2->porCpf('345');

    // Então nada é colocado em cache
    expect($service2->cache()->isValido())->toBeTrue();
});

test('Cache personalizado da query graphQL', function () {
    // Dado que existe uma query graphQL
    $service = new PessoasPolvoService();

    // Quando for executada com TTL e KEY personalizados
    $service->lembrar('10 seconds', 'servidor-345')->porCpf('345');

    // Então o TTL e KEY devem ser respeitados
    expect($service->getKey())->toBe('servidor-345')
        ->and($service->getTtl())->equalTo(now()->parse('10 seconds'));
});

test('Expiração de cache da query graphQL', function () {
    // Dado que existe uma query graphQL
    $service = new PessoasPolvoService();

    // Quando for executada com cache
    $service->porCpf('345');

    $vaiSerInvalido = $service->isValido();
    $vaiEstarExpirado = $service->isExpirado();

    // Dado que avançei no tempo em duas horas
    now()->setTestNow(now()->addHours(2));

    // Então o cache está expirado
    expect($vaiEstarExpirado)->toBeTrue();

    // Então não possui cache válido
    expect($vaiSerInvalido)->toBeTrue();

    // Então a chave não existe mais em cache
    expect($service->cache()->get())->toBeNull();
});

