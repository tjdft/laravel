<?php

namespace Tests\Feature\Auth;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Livewire\Livewire;
use Mockery\MockInterface;
use SocialiteProviders\Keycloak\KeycloakExtendSocialite;
use Workbench\App\Models\User;

beforeEach(function () {
    // Fake do Keycloak
    $this->mariaKeycloak = [
        'uuid' => 'a44740ee-4c04-495a-b282-1f22cbc8d551',
        'nome' => 'Maria Silva',
        'login' => 't123456',
        'email' => 'maria@tjdft.jus.br',
        'cpf' => '123456789',
    ];

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
            'nome' => 'Serviço de Projetos 1'
        ]
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
            'nome' => 'Serviço de Projetos 2'
        ]
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
                        'data' => $this->retornoVazio ? [] : ($this->multiplosVinculo ? [$this->joaoPolvo, $this->mariaPolvo] : [$this->mariaPolvo])
                    ]
                ]
            ]);
        }
    ]);

    // Mock da resposta do Socialite
    $this->keycloakUser = $this->mock(KeycloakExtendSocialite::class, function (MockInterface $mock) {
        $mock->shouldReceive('getId')->andReturn($this->mariaKeycloak['uuid'])
            ->shouldReceive('getName')->andReturn($this->mariaKeycloak['nome'])
            ->shouldReceive('getEmail')->andReturn($this->mariaKeycloak['email'])
            ->shouldReceive('getNickName')->andReturn($this->mariaKeycloak['login']);
    });

    $this->keycloakUser->attributes['cpf'] = $this->mariaKeycloak['cpf'];

    Socialite::shouldReceive('driver->stateless->user')->andReturn($this->keycloakUser);
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
        'uuid' => $this->mariaKeycloak['uuid'],
    ]);

    // Quando eu fizer login no keycloack
    // Então devo ser redirecionado para a página inicial do sistema
    $this->get('/auth/callback/keycloak')->assertRedirect('/');

    // Então o cadastro é realizado localmente
    $mariaLocal = User::where('uuid', $this->mariaKeycloak['uuid'])->first();

    // Então o usuário é cadastrado com dados vindos do Keycloak
    expect($mariaLocal->uuid)->toBe($this->mariaKeycloak['uuid'])
        ->and($mariaLocal->nome)->toBe($this->mariaKeycloak['nome'])
        ->and($mariaLocal->login)->toBe($this->mariaKeycloak['login'])
        ->and($mariaLocal->email)->toBe($this->mariaKeycloak['email'])
        ->and($mariaLocal->matricula)->toBe($this->mariaPolvo['matricula'])
        ->and($mariaLocal->foto)->toBe($this->mariaPolvo['foto'])
        ->and($mariaLocal->rh_tipo)->toBe($this->mariaPolvo['tipo'])
        ->and($mariaLocal->rh_status)->toBe($this->mariaPolvo['status'])
        ->and(json_encode($mariaLocal->localizacao))->toBe(json_encode($this->mariaPolvo['localizacao']));
});

test('Atualiza localmente os dados do usuário depois do login', function () {
    // Dado que o usuário já existe localmente
    User::factory()->create([
        'uuid' => $this->mariaKeycloak['uuid'],
        'cpf' => '123456789',
        'matricula' => '123456',
    ]);

    // Dado que eu não estava logado
    $this->logout();

    // Quando eu fizer login no keycloack
    // Então devo ser redirecionado para a página inicial do sistema
    $this->get('/auth/callback/keycloak')->assertRedirect('/');

    // Então o cadastro do usuário é ATUALIZADO localmente com os novos dados
    $mariaLocal = User::where('uuid', $this->mariaKeycloak['uuid'])->first();

    expect($mariaLocal->uuid)->toBe($this->mariaKeycloak['uuid'])
        ->and($mariaLocal->nome)->toBe($this->mariaKeycloak['nome'])
        ->and($mariaLocal->login)->toBe($this->mariaKeycloak['login'])
        ->and($mariaLocal->email)->toBe($this->mariaKeycloak['email'])
        ->and($mariaLocal->matricula)->toBe($this->mariaPolvo['matricula'])
        ->and($mariaLocal->foto)->toBe($this->mariaPolvo['foto'])
        ->and($mariaLocal->rh_tipo)->toBe($this->mariaPolvo['tipo'])
        ->and($mariaLocal->rh_status)->toBe($this->mariaPolvo['status'])
        ->and(json_encode($mariaLocal->localizacao))->toBe(json_encode($this->mariaPolvo['localizacao']));
});

test('Usuários que não estão na API RH', function () {
    // Desliga o tratamento de exceções
    $this->withoutExceptionHandling();

    // Dado que eu não estava logado
    $this->logout();

    // Dado que a API RH não retorna vínculos para este CPF
    $this->retornoVazio = true;

    // Quando eu fizer login no keycloack
    // Então deve ser lançada uma exceção de autorização
    $this->get('/auth/callback/keycloak');
})->throws(AuthorizationException::class, 'O CPF (123456789) não está cadastrado ou não possui acesso nesta aplicação.');

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

test('Usuário sem CPF no Keycloak não é autenticado', function () {
    // Desliga o tratamento de exceções
    $this->withoutExceptionHandling();

    // Dado que eu não estava logado
    $this->logout();

    // Dado que o Keycloak retornou um usuário sem CPF
    $this->keycloakUser->attributes['cpf'] = null;

    // Quando eu tentar fazer o callback
    // Então deve ser lançada uma exceção de autorização
    $this->get('/auth/callback/keycloak');
})->throws(AuthorizationException::class, 'Usuário não possui CPF cadastrado no Keycloak.');

test('Invoca a action de Permissions adicionais da aplicação local', function () {
    // Dado que eu não estava logado
    $this->logout();

    // Mock da action de permissões
    $this->mock(config('tjdft.permissions_action'), function (MockInterface $mock) {
        $mock->shouldReceive('execute')->once();
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
