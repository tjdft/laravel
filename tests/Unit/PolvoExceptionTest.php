<?php

use Illuminate\Support\Facades\Http;
use TJDFT\Laravel\Exceptions\PolvoException;
use TJDFT\Laravel\Services\PessoasPolvoService;

//beforeEach(function () {
//    Http::fake(['/graphql' => ['não importa']]);
//});

test('Resposta vazia', function () {
    Http::fake(['/graphql' => Http::response()]);

    new PessoasPolvoService()->porCpf(123);
    //
})->throws(PolvoException::class, 'PolvoService: Erro de conectividade, resposta vazia.');

test('Erro graphQL conhecido', function () {
    Http::fake([
        '/graphql' => [
            'errors' => [
                [
                    'message' => 'erro retornado fake',
                ],
            ]
        ]
    ]);

    new PessoasPolvoService()->porCpf(123);
    //
})->throws(PolvoException::class, 'PolvoService : Erro API : erro retornado fake');

test('Erro graphQL desconhecido', function () {
    Http::fake([
        '/graphql' => [
            'errors' => [
                [],
            ]
        ]
    ]);

    new PessoasPolvoService()->porCpf(123);
    //
})->throws(PolvoException::class, 'PolvoService : Erro API : erro desconhecido');


