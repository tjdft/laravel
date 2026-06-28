<?php

namespace Tests\Feature\Auth;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Livewire\Livewire;
use Workbench\App\Models\User;

beforeEach(function () {
    // Fake do Keycloak
    $this->mariaKeycloak = new SocialiteUser()->map([
        'id' => 'a44740ee-4c04-495a-b282-1f22cbc8d999',
        'name' => 'Maria Silva',
        'nickname' => 't123456',
        'email' => 'maria@tjdft.jus.br',
        'user' => [
            'cpf' => '123456789',
        ]
    ]);

    $this->mariaKeycloak->attributes = null;

    Socialite::fake('keycloak', $this->mariaKeycloak);

    // Fake do Polvo
    $this->mariaPolvo = [
        'nomeFinal' => 'Maria Silva',
        'cpf' => '123456789',
        'matricula' => '123456',
        'foto' => 'https://tjdft.jus.br/123456.jpg',
        'tipo' => 'SERVIDOR',
        'status' => 'ATIVO',
        'localizacao' => [
            'id' => '200120',
            'codigo' => '200120',
            'sigla' => 'PROJ1',
            'nome' => 'Serviço de Projetos 1',
        ],
    ];

    // Fake do Polvo
    $this->joaoPolvo = [
        'nomeFinal' => 'João Silva',
        'cpf' => '123456789',
        'matricula' => '4444',
        'foto' => 'https://tjdft.jus.br/123456.jpg',
        'tipo' => 'PENSAO_ALIMENTICIA',
        'status' => 'ATIVO',
        'localizacao' => [
            'id' => '200140',
            'codigo' => '200140',
            'sigla' => 'PROJ2',
            'nome' => 'Serviço de Projetos 2',
        ],
    ];

    // Condições de retorno do Polvo
    $this->retornoVazio = false;
    $this->multiplosVinculo = false;

    // Mock da resposta do Polvo
    Http::fake([
        '/graphql' => function () {
            return Http::response([
                'data' => [
                    'pessoas' => [
                        'data' => $this->retornoVazio ? [] : ($this->multiplosVinculo ? [$this->joaoPolvo, $this->mariaPolvo] : [$this->mariaPolvo]),
                    ],
                ],
            ]);
        },
    ]);
});

test('Após o login redireciona para url que estava tentando acessar', function () {
    // Dado que eu não estava logado
    $this->logout();

    // Dado que eu tento acessar uma página protegida
    $this->get('/dashboard/index');

    // Quando eu fizer login no keycloack
    // Então devo ser redirecionado para a página que eu estava tentando acessar
    $this->get('/auth/callback/keycloak')->assertRedirect('/dashboard/index');
});

test('Cadastra usuário localmente na aplicação após o login', function () {
    // Dado que eu não estava logado
    $this->logout();

    // Dado que o usuário não existe localmente
    $this->assertDatabaseMissing('users', [
        'uuid' => $this->mariaKeycloak->getId(),
    ]);

    // Quando eu fizer login no keycloack
    // Então devo ser redirecionado para a página inicial do sistema
    $this->get('/auth/callback/keycloak')->assertRedirect('/');

    // Então o cadastro é realizado localmente
    $mariaLocal = User::where('uuid', $this->mariaKeycloak->getId())->first();

    // Então o usuário é cadastrado com dados vindos do Keycloak
    expect($mariaLocal->uuid)->toBe($this->mariaKeycloak->getId())
        ->and($mariaLocal->nome)->toBe($this->mariaKeycloak->getName())
        ->and($mariaLocal->login)->toBe($this->mariaKeycloak->getNickname())
        ->and($mariaLocal->email)->toBe($this->mariaKeycloak->getEmail())
        ->and($mariaLocal->matricula)->toBe($this->mariaPolvo['matricula'])
        ->and($mariaLocal->foto)->toBe($this->mariaPolvo['foto'])
        ->and($mariaLocal->rh_tipo)->toBe($this->mariaPolvo['tipo'])
        ->and($mariaLocal->rh_status)->toBe($this->mariaPolvo['status'])
        ->and(json_encode($mariaLocal->localizacao))->toBe(json_encode($this->mariaPolvo['localizacao']));
});

test('Atualiza localmente os dados do usuário depois do login', function () {
    // Dado que o usuário já existe localmente
    User::factory()->create([
        'uuid' => $this->mariaKeycloak->getId(),
        'cpf' => $this->mariaPolvo['cpf'],
        'matricula' => $this->mariaPolvo['matricula'],
    ]);

    // Dado que eu não estava logado
    $this->logout();

    // Quando eu fizer login no keycloack
    // Então devo ser redirecionado para a página inicial do sistema
    $this->get('/auth/callback/keycloak')->assertRedirect('/');

    // Então o cadastro do usuário é ATUALIZADO localmente com os novos dados
    $mariaLocal = User::where('uuid', $this->mariaKeycloak->getId())->first();

    expect($mariaLocal->uuid)->toBe($this->mariaKeycloak->getId())
        ->and($mariaLocal->nome)->toBe($this->mariaKeycloak->getName())
        ->and($mariaLocal->login)->toBe($this->mariaKeycloak->getNickname())
        ->and($mariaLocal->email)->toBe($this->mariaKeycloak->getEmail())
        ->and($mariaLocal->matricula)->toBe($this->mariaPolvo['matricula'])
        ->and($mariaLocal->foto)->toBe($this->mariaPolvo['foto'])
        ->and($mariaLocal->rh_tipo)->toBe($this->mariaPolvo['tipo'])
        ->and($mariaLocal->rh_status)->toBe($this->mariaPolvo['status'])
        ->and(json_encode($mariaLocal->localizacao))->toBe(json_encode($this->mariaPolvo['localizacao']));
});

test('Usuários que não estão na API RH tem seu cadastro criado usando CPF', function () {
    // Dado que eu não estava logado
    $this->logout();

    // Dado que a API RH não retorna vínculos para este CPF
    $this->retornoVazio = true;

    // Quando eu fizer login no keycloack
    $this->get('/auth/callback/keycloak');

    $mariaLocal = User::where('uuid', $this->mariaKeycloak->getId())->first();

    // Então será criado um usuário com matrícula igual ao próprio CPF
    expect($mariaLocal->uuid)->toBe($this->mariaKeycloak->getId())
        ->and($mariaLocal->cpf)->toBe($this->mariaKeycloak->user['cpf'])
        ->and($mariaLocal->matricula)->toBe($this->mariaKeycloak->user['cpf']);
});

test('Usuário sem CPF no Keycloak não é autenticado', function () {
    // Desliga o tratamento de exceções
    $this->withoutExceptionHandling();

    // Dado que eu não estava logado
    $this->logout();

    // Dado que o Keycloak retornou um usuário sem CPF
    $this->mariaKeycloak->user['cpf'] = null;

    // Quando eu tentar fazer o callback
    // Então deve ser lançada uma exceção de autorização
    $this->get('/auth/callback/keycloak');
})->throws(AuthorizationException::class, 'Usuário não possui CPF cadastrado no Keycloak.');

test('No Keycloak o CPF é uma string simples no campo `attributes`', function () {
    // Dado que eu não estava logado
    $this->logout();

    // Dado que o Keycloak retornou o CPF via `attributes`
    $this->mariaKeycloak->user['cpf'] = null;
    $this->mariaKeycloak->attributes['cpf'] = '123456789';

    // Quando eu fizer login no keycloack
    $this->get('/auth/callback/keycloak')->assertRedirect('/');

    // Então o cadastro é realizado localmente
    $mariaLocal = User::where('uuid', $this->mariaKeycloak->getId())->first();

    expect($mariaLocal->uuid)->toBe($this->mariaKeycloak->getId());
});

test('No Keycloak o CPF é um array do campo `attributes`', function () {
    // Dado que eu não estava logado
    $this->logout();

    // Dado que o Keycloak retornou o CPF via `attributes`
    $this->mariaKeycloak->user['cpf'] = null;
    $this->mariaKeycloak->attributes['cpf'] = ['123456789'];

    // Quando eu fizer login no keycloack
    $this->get('/auth/callback/keycloak')->assertRedirect('/');

    // Então o cadastro é realizado localmente
    $mariaLocal = User::where('uuid', $this->mariaKeycloak->getId())->first();

    expect($mariaLocal->uuid)->toBe($this->mariaKeycloak->getId());
});

test('No keycloak o CPF é um array do campo `user`', function () {
    // Dado que eu não estava logado
    $this->logout();

    // Dado que o Keycloak retornou o CPF via `user`
    $this->mariaKeycloak->user['cpf'] = ['123456789'];

    // Quando eu fizer login no keycloack
    $this->get('/auth/callback/keycloak')->assertRedirect('/');

    // Então o cadastro é realizado localmente
    $mariaLocal = User::where('uuid', $this->mariaKeycloak->getId())->first();

    expect($mariaLocal->uuid)->toBe($this->mariaKeycloak->getId());
});

test('Redireciona para `/auth/perfil` quando há múltiplos vínculos com o RH', function () {
    // Dado que eu não estava logado
    $this->logout();

    // Dado que a API RH retorna múltiplos vínculos para este CPF
    $this->multiplosVinculo = true;

    // Quando eu fizer login no keycloack
    // Então devo ser redirecionado para a página de seleção de perfil
    $this->get('/auth/callback/keycloak')->assertRedirect('/auth/perfil');

    // Então os vínculos são cadastrados localmente
    $this->assertDatabaseHas('users', [
        'cpf' => '123456789',
        'matricula' => $this->mariaPolvo['matricula'],
    ]);

    // Então os vínculos são cadastrados localmente
    $this->assertDatabaseHas('users', [
        'cpf' => '123456789',
        'matricula' => $this->joaoPolvo['matricula'],
    ]);

    // Então o primeiro vínculo é logado por padrão
    expect(auth()->user()->nome)->toBe('João Silva');
});

test('Seleciona um vínculo de RH para iniciar sessão', function () {
    // Dado que eu não estava logado
    $this->logout();

    // Dado que eu possuo vinculos múltiplos
    $vinculo1 = User::factory()->create([
        'cpf' => '123456789',
        'matricula' => '4444',
    ]);

    // Dado que eu possuo vinculos múltiplos
    $vinculo2 = User::factory()->create([
        'cpf' => '123456789',
        'matricula' => '5555',
    ]);

    // Dado que eu estou logado com o primeiro vínculo
    $this->login($vinculo1);

    // Quando eu selecionar o segundo vínculo na tela de perfil
    // Então devo ser redirecionado para a página inicial do sistema
    Livewire::test('tjdft::perfil')
        ->call('iniciar', $vinculo2->matricula)
        ->assertRedirect('/');

    // Então o segundo vínculo é o que está logado na sessão
    expect(auth()->user()->id)->toBe($vinculo2->id);
});

test('Invoca a action de Permissions adicionais da aplicação local', function () {
    // Dado que eu não estava logado
    $this->logout();

    // Mock da action de permissões
    $mock = $this->mock(config('tjdft.permissions_action'));
    $mock->shouldReceive('execute')->once();

    // Use bind em vez de instance ou do helper $this->mock()
    $this->app->bind(config('tjdft.permissions_action'), function () use ($mock) {
        return $mock;
    });

    // Quando eu fizer login no keycloack
    // Então a action de permissões deve ser invocada
    // E devo ser redirecionado para a página inicial do sistema
    $this->get('/auth/callback/keycloak')->assertRedirect('/');
});

test('Não falha se a action de Permissions adicionais não existir', function () {
    // Dado que eu não estava logado
    $this->logout();

    // Dado que a action de permissões está configurada para uma classe inexistente
    config()->set('tjdft.permissions_action', 'App\Actions\ClasseInexistente');

    // Quando eu fizer login no keycloack
    // Então não deve lançar exceção
    // E devo ser redirecionado para a página inicial do sistema
    $this->get('/auth/callback/keycloak')->assertRedirect('/');
});
