<?php

use Workbench\App\Models\User;

test('Atribui role para usuário', function () {
    // Cria um usuário de teste
    $user = User::factory()->create();

    // Atribui uma role ao usuário
    $user->assignRole('admin');

    // Verifica se a role foi atribuída corretamente
    $this->assertDatabaseHas('acl_grants', [
        'user_id' => $user->id,
        'roles' => json_encode(['admin']),
    ]);

    // Verifica se o usuário possui a role atribuída
    expect($user->hasRole('admin'))->toBeTrue();
});

test('Remove role do usuário', function () {
    // Cria um usuário de teste
    $user = User::factory()->create();

    // Atribui uma role ao usuário
    $user->assignRole('admin');

    // Remove a role do usuário
    $user->unassignRole('admin');

    // Verifica se a role foi removida corretamente
    $this->assertDatabaseHas('acl_grants', [
        'user_id' => $user->id,
        'roles' => json_encode([]),
    ]);

    // Verifica se o usuário não possui mais a role
    expect($user->hasRole('admin'))->toBeFalse();
});

test('Sincroniza roles do usuário', function () {
    // Cria um usuário de teste
    $user = User::factory()->create();

    // Sincroniza múltiplas roles ao usuário
    $user->syncRoles(['editor', 'moderator']);

    // Verifica se as roles foram sincronizadas corretamente
    $this->assertDatabaseHas('acl_grants', [
        'user_id' => $user->id,
        'roles' => json_encode(['editor', 'moderator']),
    ]);

    // Verifica se o usuário possui as roles sincronizadas
    expect($user->hasRole('editor'))->toBeTrue();
    expect($user->hasRole('moderator'))->toBeTrue();
});

test('Verifica se usuário possui qualquer uma das roles da lista', function () {
    // Cria um usuário de teste
    $user = User::factory()->create();

    // Atribui múltiplas roles ao usuário
    $user->assignRole('admin');

    // Verifica se o usuário possui qualquer uma das roles
    expect($user->hasAnyRole(['admin', 'moderator']))->toBeTrue();
});

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
})->todo()->skip();

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

    // Atribui a role de administrador ao usuário
    $user->assignRole('admin');

    // Verifica se o usuário é administrador
    expect($user->isAdmin())->toBeTrue();
});
