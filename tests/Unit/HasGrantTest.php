<?php

use Workbench\App\Models\User;

test('Atribui permission para usuário', function () {
    // Cria um usuário de teste
    $user = User::factory()->create();

    // Atribui uma permission ao usuário
    $user->givePermissionTo('edit.posts');

    // Verifica se a permission foi atribuída corretamente
    $this->assertDatabaseHas('acl_grants', [
        'user_id' => $user->id,
        'permissions' => json_encode(['edit.posts']),
    ]);

    // Verifica se o usuário possui a permission atribuída
    expect($user->hasPermission('edit.posts'))->toBeTrue();
});

test('Remove permission do usuário', function () {
    // Cria um usuário de teste
    $user = User::factory()->create();

    // Atribui uma permission ao usuário
    $user->givePermissionTo('edit.posts');

    // Remove a permission do usuário
    $user->revokePermissionTo('edit.posts');

    // Verifica se a permission foi removida corretamente
    $this->assertDatabaseHas('acl_grants', [
        'user_id' => $user->id,
        'permissions' => json_encode([]),
    ]);

    // Verifica se o usuário não possui mais a permission
    expect($user->hasPermission('edit.posts'))->toBeFalse();
});

test('Sincroniza permissions do usuário', function () {
    // Cria um usuário de teste
    $user = User::factory()->create();

    // Sincroniza múltiplas permissions ao usuário
    $user->syncPermissions(['create.posts', 'delete.posts']);

    // Verifica se as permissions foram sincronizadas corretamente
    $this->assertDatabaseHas('acl_grants', [
        'user_id' => $user->id,
        'permissions' => json_encode(['create.posts', 'delete.posts']),
    ]);

    // Verifica se o usuário possui as permissions sincronizadas
    expect($user->hasPermission('create.posts'))->toBeTrue();
    expect($user->hasPermission('delete.posts'))->toBeTrue();
});

test('Verificar se usuário possui qualquer uma das permissions da lista', function () {
    // Cria um usuário de teste
    $user = User::factory()->create();

    // Atribui múltiplas permissions ao usuário
    $user->givePermissionTo('edit.posts');

    // Verifica se o usuário possui qualquer uma das permissions
    expect($user->hasAnyPermission(['edit.posts', 'delete.posts']))->toBeTrue();
});

test('Autoriza usuário com permission específica', function () {
    // Cria um usuário de teste
    $user = User::factory()->create();

    // Atribui uma permission ao usuário
    $user->givePermissionTo('view.dashboard');

    // Tenta autorizar o usuário com a permission atribuída
    $user->authorize('view.dashboard');

    // Se não lançar exceção, o teste passa
    expect(true)->toBeTrue();
});

test('Nega autorização para usuário sem permission específica', function () {
    // Cria um usuário de teste
    $user = User::factory()->create();

    // Tenta autorizar o usuário sem a permission atribuída
    $user->authorize('manage.users');
})->throws(Exception::class);

test('Testa via can()', function () {
    // Cria um usuário de teste
    $user = User::factory()->create();

    // Atribui uma permission ao usuário
    $user->givePermissionTo('edit.posts');

    // Verifica se o usuário pode realizar a ação
    expect($user->can('edit.posts'))->toBeTrue();
});

test('Testa via cannot()', function () {
    // Cria um usuário de teste
    $user = User::factory()->create();

    // Atribui uma permission ao usuário
    $user->givePermissionTo('edit.posts');

    // Verifica se o usuário não pode realizar uma ação não atribuída
    expect($user->cannot('delete.posts'))->toBeTrue();
});

test('Verificar se usuário é administrador', function () {
    // Cria um usuário de teste
    $user = User::factory()->create();

    // Atribui a permissão de administrador
    $user->givePermissionTo('permissoes.gerenciar');

    // Verifica se o usuário é administrador
    expect($user->isAdmin())->toBeTrue();
});
