<?php

use Livewire\Livewire;
use TJDFT\Laravel\Models\Permission;
use Workbench\App\Models\User;

test('Visitantes não podem gerenciar', function () {
    // Dado que não estou logado
    $this->logout();

    // Quando tento acessar a página de gerenciamento de permissões
    // Então sou redirecionado para a página de login
    $this->get('/auth/permissions')->assertRedirect('/login');
});

test('Usuários sem permissão não podem gerenciar', function () {
    // Dado que estou logado e não tenho permissão

    // Quando tento acessar a página de gerenciamento de permissões
    // Então vejo uma mensagem de acesso negado
    $this->get('/auth/permissions')->assertForbidden();
});

test('Somente usuário com permissão pode gerenciar', function () {
    // Dado que estou logado com permissão de gerenciar permissões
    $this->login(permission: 'permissoes.gerenciar');

    // Quando acesso a página de gerenciamento de permissões
    // Então vejo a página corretamente
    Livewire::test('tjdft::permissions.index')
        ->assertOk();
});

test('Visualiza usuários cadastrados', function () {
    // Dado que estou logado com permissão de gerenciar permissões
    $this->login(permission: 'permissoes.gerenciar');

    // Dado que existem usuários cadastrados
    $maria = User::factory()->create(['nome' => 'Maria']);
    $jose = User::factory()->create(['nome' => 'José']);
    $pedro = User::factory()->create(['nome' => 'Pedro']);

    // Quando acesso a página de gerenciamento de permissões
    // Então vejo a lista de usuários
    Livewire::test('tjdft::permissions.index')
        ->assertSee($maria->nome)
        ->assertSee($jose->nome)
        ->assertSee($pedro->nome);
});

test('Pesquisa por usuários', function () {
    // Dado que estou logado com permissão de gerenciar permissões
    $this->login(permission: 'permissoes.gerenciar');

    // Dado que existe um usuário cadastrado
    $user = User::factory()->create(['nome' => 'João da Silva']);

    // Quando faço uma busca por João
    // Então vejo Jonão na lista
    // Então não vejo Maria na lista
    Livewire::test('tjdft::permissions.index')
        ->set('search', 'João')
        ->assertSee($user->nome)
        ->set('search', 'Maria')
        ->assertDontSee($user->nome);
});

test('Pesquisa por permissões - TODAS', function () {
    // Dado que estou logado com permissão de gerenciar permissões
    $this->login(permission: 'permissoes.gerenciar');

    // Dado que existe um usuário cadastrado
    $user = User::factory()->create();

    // Dado que existe uma permissão cadastrada
    Permission::create(['name' => 'paginas.criar', 'description' => 'Permite criar páginas']);

    // Quando faço uma busca por parte do nome da permissão
    // Então vejo a permissão na lista
    // Então não vejo a permissão na lista ao buscar por termo inexistente
    Livewire::test('tjdft::permissions.show', ['user' => $user->id])
        ->set('search_all_permission', 'pagi')
        ->assertSee('paginas.criar')
        ->set('search_all_permission', 'xxx')
        ->assertDontSee('paginas.criar');
});

test('Pesquisa por permissões - SELECIONADAS', function () {
    // Dado que estou logado com permissão de gerenciar permissões
    $this->login(permission: 'permissoes.gerenciar');

    // Dado que existe um usuário cadastrado
    $user = User::factory()->create();

    // Dado que existe uma permissão cadastrada e atribuída ao usuário
    Permission::create(['name' => 'paginas.criar', 'description' => 'Permite criar páginas']);
    $user->givePermissionTo('paginas.criar');

    // Quando faço uma busca por parte do nome da permissão selecionada
    // Então vejo a permissão na lista
    // Então não vejo a permissão na lista ao buscar por termo inexistente
    Livewire::test('tjdft::permissions.show', ['user' => $user->id])
        ->set('search_selected_permission', 'pagi')
        ->assertSee('paginas.criar')
        ->set('search_all_permission', 'xxx')
        ->set('search_selected_permission', 'xxx')
        ->assertDontSee('paginas.criar');
});

test('Altera permissões do usuário', function () {
    // Dado que estou logado com permissão de gerenciar permissões
    $this->login(permission: 'permissoes.gerenciar');

    // Dado que existe um usuário cadastrado
    $user = User::factory()->create();

    // Dado que existe uma permissão cadastrada
    $permission = Permission::create(['name' => 'paginas.criar', 'description' => 'Permite criar páginas']);

    // Quando atribuo a permissão ao usuário
    // Então vejo a permissão na lista de permissões selecionadas
    Livewire::test('tjdft::permissions.show', ['user' => $user->id])
        ->set('selection', [$permission->name])
        ->call('salvar')
        ->set('search_all_permission', 'xxxx')
        ->assertSee('paginas.criar')
        ->assertOk();

    // Quando removo a permissão do usuário
    // Então não vejo mais a permissão na lista de permissões selecionadas
    Livewire::test('tjdft::permissions.show', ['user' => $user->id])
        ->set('selection', [])
        ->call('salvar')
        ->set('search_all_permission', 'xxxx')
        ->assertDontSee('paginas.criar')
        ->assertOk();
});

test('Copiar permissões de um usuário para outro', function () {
    // Dado que estou logado com permissão de gerenciar permissões
    $this->login(permission: 'permissoes.gerenciar');

    // Dado que existem dois usuários cadastrados
    $usuario_com_permissao = User::factory()->create();
    $usuario_sem_permissao = User::factory()->create();

    // Dado que existe uma permissão cadastrada e atribuída ao usuário A
    $permission = Permission::create(['name' => 'paginas.criar', 'description' => 'Permite criar páginas']);
    $usuario_com_permissao->givePermissionTo($permission->name);

    // Dado que o usuário B não possui a permissão
    expect($usuario_sem_permissao->hasPermission($permission->name))->toBeFalse();

    // Dado que escolho o usuário que possui permissões
    // Quando copio as permissões do usuário A para o usuário B
    // Então o usuário B possui a permissão copiada
    Livewire::test('tjdft::permissions.show', ['user' => $usuario_sem_permissao->id])
        ->set('user_id', $usuario_com_permissao->id)
        ->call('copiar')
        ->assertOk();

    expect($usuario_sem_permissao->refresh()->hasPermission($permission->name))->toBeTrue();
});
