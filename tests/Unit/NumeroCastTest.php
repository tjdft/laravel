<?php

use Workbench\App\Models\User;

test('Cast de número ao salvar', function () {
    $user = User::factory()->create();

    $user->update(['cpf' => '111.222.333-44']);

    $this->assertDatabaseHas('users', [
        'cpf' => '11122233344'
    ]);

    expect($user->refresh()->cpf)->toBe('11122233344');
});




