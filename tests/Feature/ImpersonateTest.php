<?php

namespace Tests\Feature\Impersonate;

use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Workbench\App\Models\User;

test('Visitantes não podem personificar.', function () {
    // Dado que não estou logado
    $this->logout();

    // Quando tento acessar a página de personificação
    // Então sou redirecionado para a página de login
    $this->get('/auth/impersonate')->assertRedirect('/login');
});

test('Usuários sem permissão não podem personificar.', function () {
    // Dado que estou logado e não tenho permissão

    // Quando tento acessar a página de personificação
    // Então vejo uma mensagem de acesso negado
    $this->get('/auth/impersonate')->assertForbidden();
});

test('Somente pessoas com permissão podem personificar.', function () {
    // Dado que estou logado com permissão de personificação
    $this->login(permission: 'impersonate');

    // Quando tento acessar a página de personificação
    // Então consigo acessar normalmente
    $this->get('/auth/impersonate')->assertOk();
});

test('Não pode personificar 2 usuários ao mesmo tempo.', function () {
    // Dado que estou logado com permissão de personificação
    $this->login(permission: 'impersonate');

    // Dado que existe um usuário para ser personificado
    $usario_personificado = User::factory()->create();

    // Dado que este usuário também possui permissão de personificação
    $usario_personificado->givePermissionTo('impersonate');

    // Dado já estou personificando este usuário
    auth()->user()->impersonate($usario_personificado);

    // Quando tento personificar novamente
    // Então vejo uma mensagem de erro
    Livewire::test('tjdft::impersonate')
        ->set('login', 't123456')
        ->call('impersonate')
        ->assertSee('Você já está personificando alguém.');
});

test('Não pode personificar um login INEXISTENTE no RH', function () {
    // Fake Graphql para simular login inexistente
    Http::fake(['/graphql' => [null]]);

    // Dado que estou logado com permissão de personificação
    $usuario_original = $this->login(permission: 'impersonate');

    // Quando tento personificar um login inexistente
    // Então vejo uma mensagem de erro
    Livewire::test('tjdft::impersonate')
        ->set('login', 'NAO_EXISTE')
        ->call('impersonate')
        ->assertSee('Login não encontrado.');

    // Então o usuário logado continua o mesmo
    expect(auth()->user()->nome)->toBe($usuario_original->nome);
});

test('Não pode personificar administradores', function () {
    // Dado que existe um usuário no RH, mas que localmente no sistema é um administrador
    Http::fake([
        '/graphql' => [
            'data' => [
                'pessoas' => [
                    'data' => [
                        [
                            'Nome' => 'Administrador Teste',
                            'cpf' => '12345678900',
                            'matricula' => '999999',
                            'login' => 't999999',
                        ]
                    ]
                ]
            ]
        ]
    ]);

    // Dado que existe ele existe localmente no sistema
    User::factory()->create([
        'cpf' => '12345678900',
        'matricula' => '999999',
        'login' => 't999999',
    ])->assignRole('admin');

    // Dado que estou logado com permissão de personificação
    $usuario_original = $this->login(permission: 'impersonate');

    // Quando tento personificar
    // Então vejo uma mensagem de erro
    Livewire::test('tjdft::impersonate')
        ->set('login', 't999999')
        ->call('impersonate')
        ->assertSee('Não é permitido personificar um administrador.');

    // Então o usuário logado continua o mesmo
    expect(auth()->user()->nome)->toBe($usuario_original->nome);
});

test('Não pode personificar pessoas sem CPF ou MATRÍCULA', function () {
    // Dado que existe um usuário no RH sem CPF ou matrícula
    Http::fake([
        '/graphql' => [
            'data' => [
                'pessoas' => [
                    'data' => [
                        [
                            'nomeFinal' => 'Administrador Teste',
                            'login' => 't999999',
                        ]
                    ]
                ]
            ]
        ]
    ]);

    // Dado que ele existe localmente no sistema
    User::factory()->create([
        'cpf' => '12345678900',
        'matricula' => '999999',
        'login' => 't999999',
    ]);

    // Dado que estou logado com permissão de personificação
    $usuario_original = $this->login(permission: 'impersonate');

    // Quando tento personificar
    // Então vejo uma mensagem de erro
    Livewire::test('tjdft::impersonate')
        ->set('login', 't999999')
        ->call('impersonate')
        ->assertSee('Pessoa sem CPF ou matrícula não pode ser personificada.');

    // Então o usuário logado continua o mesmo
    expect(auth()->user()->nome)->toBe($usuario_original->nome);
});

test('Personifica um usuário EXISTENTE no sistema local', function () {
    // Dado que estou logado com permissão de personificação
    $this->login(permission: 'impersonate');

    // Dado que existe um usuário para ser personificado
    $usuario_personificado = User::factory()->create();

    // Dado que este usuário existe na API RH
    Http::fake([
        '/graphql' => [
            'data' => [
                'pessoas' => [
                    'data' => [
                        [
                            'nomeFinal' => 'Pessoa nome alterado',
                            'cpf' => $usuario_personificado->cpf,
                            'matricula' => $usuario_personificado->matricula,
                            'login' => $usuario_personificado->login
                        ]
                    ]
                ]
            ]
        ]
    ]);

    // Dado o total de usuários antes da personificação
    $total_usuarios = User::count();

    // Quando personifico este usuário
    Livewire::test('tjdft::impersonate')
        ->set('login', $usuario_personificado->login)
        ->call('impersonate')
        ->assertRedirect('/');

    // Então a quantidade de usuários continua a mesma
    $this->assertDatabaseCount('users', $total_usuarios);

    // Então o usuário logado é o personificado
    // E o nome foi atualizado conforme o RH
    expect(auth()->user()->nome)->toBe('Pessoa nome alterado');
});

test('Personifica um usuário INEXISTENTE no sistema local', function () {
    // Dado que estou logado com permissão de personificação
    $this->login(permission: 'impersonate');

    // Dado que existe um usuário na API RH
    Http::fake([
        '/graphql' => [
            'data' => [
                'pessoas' => [
                    'data' => [
                        [
                            'nomeFinal' => 'Pessoa Nova',
                            'cpf' => '12345678900',
                            'matricula' => '999999',
                            'login' => 't999999',
                        ]
                    ]
                ]
            ]
        ]
    ]);

    // Dado que o usuário não existe localmente no sistema
    $this->assertDatabaseMissing('users', ['nome' => 'Pessoa Nova']);

    // Quando personifico este usuário
    Livewire::test('tjdft::impersonate')
        ->set('login', 't999999')
        ->call('impersonate')
        ->assertRedirect('/');

    // Então o usuário foi criado localmente
    $this->assertDatabaseHas('users', ['nome' => 'Pessoa Nova']);

    // Então o usuário logado é o personificado
    expect(auth()->user()->nome)->toBe('Pessoa Nova');
});

test('Sair da personificação', function () {
    // Dado que estou logado com permissão de personificação
    $usuario_original = $this->login(permission: 'impersonate');

    // Dado que existe um usuário para ser personificado
    $usuario_personificado = User::factory()->create();

    // Dado que já estou personificando este usuário
    auth()->user()->impersonate($usuario_personificado);

    // Dado que o usuário logado é o personificado
    expect(auth()->user()->nome)->toBe($usuario_personificado->nome);

    // Quando sair da personificação
    Livewire::test('tjdft::impersonating')
        ->call('unpersonate')
        ->assertRedirect('/');

    // Então o usuário logado volta a ser o original
    expect(auth()->user()->nome)->toBe($usuario_original->nome);
});
