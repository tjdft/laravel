<?php

use Illuminate\Support\Facades\Http;
use TJDFT\Laravel\Services\PessoasPolvoService;

beforeEach(function () {
    Http::fake(['/graphql' => ['não importa']]);
});

test('Verifica se a query contém o trecho esperado', function () {
    new PessoasPolvoService()->porCpf(123);

    $this->assertPolvoQueryContains('cpf: "123"');
});

test('Verifica se a query NÃO contém o trecho esperado', function () {
    new PessoasPolvoService()->porLogin(123);

    $this->assertPolvoQueryNotContains('cpf: "123"');
    $this->assertPolvoQueryContains('login: "123"');
});

test('Cria usuário com permissões', function () {
    $user = $this->login(permission: ['edit articles', 'delete articles']);

    expect($user->can('edit articles'))->toBeTrue();
    expect($user->can('delete articles'))->toBeTrue();
});
