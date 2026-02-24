<?php

use Livewire\Livewire;
use Workbench\App\Models\User;

test('Configuração de exibição de campos de auditoria', function () {
    $user = User::factory()->create();

    $user->update([
        'type_id' => 1,
        'nome' => 'Nome alterado',
        'ativo' => false,
        'localizacao' => ['nome' => 'Minha Localização', 'sigla' => 'DF', 'codigo' => '12345']
    ]);

    Livewire::test('tjdft::auditoria', ['model' => $user])
        ->set('show', true)
        ->assertSee('Nome alterado')
        ->assertSee('DF')
        ->assertSee('12345')
        ->assertSee('Não')
        ->assertSee('Tipo de usuário')
        ->assertSee('Admin')
        ->assertDontSee('Minha Localização');
});
