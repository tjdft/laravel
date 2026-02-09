<?php

use Workbench\App\Models\User;

test('Pesquisa em múltiplas colunas', function () {
    // Dado que existe um usuário com `maria` no nome
    $maria1 = User::factory()->create([
        'nome' => 'Maria Silva',
        'email' => 'silva@maria.com',
    ]);

    // Dado que existe um usuário com `maria` no email
    $joao = User::factory()->create([
        'nome' => 'João Silva',
        'email' => 'joao-maria@joa.com'
    ]);

    // Esse não deve aparecer nos resultados
    User::factory()->create([
        'nome' => 'Carlos Souza',
        'email' => 'carlos@carlos.com'
    ]);

    // Realiza a busca usando o trait HasSearchAny
    $users = User::query()->searchAny(['nome', 'email'], 'maria')->get();

    // Verifica se os resultados contêm apenas os registros esperados
    expect($users->count())->toBe(2)
        ->and($users[0]->nome)->toBe($maria1->nome)
        ->and($users[1]->nome)->toBe($joao->nome);
});

test('Pesquisa em campo JSON', function () {
    // Dado que existe um usuário com localização que contém `rio`
    $jorge = User::factory()->create([
        'nome' => 'Jorge',
        'localizacao' => ['cidade' => 'RIXXA de Janeiro']
    ]);

    // Dado que existe um usuário com nome que contém `rio`
    $maria = User::factory()->create([
        'nome' => 'Maria RIXXA',
        'localizacao' => ['cidade' => 'Belo Horizonte']
    ]);

    // Esse NÃO deve aparecer nos resultados
    User::factory()->create([
        'nome' => 'André',
        'localizacao' => ['cidade' => 'São Paulo']
    ]);

    // Quando realiza a busca
    $users = User::query()->searchAny(['localizacao->cidade', 'nome'], 'rixxa')->get();

    // Então verifica se os resultados contêm apenas os registros esperados
    expect($users->count())->toBe(2)
        ->and($users[0]->nome)->toBe($jorge->nome)
        ->and($users[1]->nome)->toBe($maria->nome);
});

test('Pesquisa ignora acentos e CASE SENSITIVE', function () {
    // Dado que existe um usuário com nome acentuado
    $ana = User::factory()->create([
        'nome' => 'Ana Júlia',
    ]);

    // Dado que existe um usuário com nome em maiúsculas
    $carlos = User::factory()->create([
        'nome' => 'CARLOS SANTOS',
    ]);

    // Quando realiza a busca
    $users = User::query()->searchAny(['nome'], 'ANÃ juliÁ')->get();

    // Então verifica se o resultado contém o registro esperado
    expect($users->count())->toBe(1)
        ->and($users[0]->nome)->toBe($ana->nome);
});
