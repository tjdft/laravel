<?php

use Workbench\App\Models\User;

test('Cast de competência ao salvar e recuperar', function () {
    $user = User::factory()->create();

    $carbon = now();

    $user->update(['competencia' => $carbon]);

    $this->assertDatabaseHas('users', [
        'competencia' => now()->format('Ym'),
    ]);

    expect($user->refresh()->competencia->format('Ym'))->toBe($carbon->format('Ym'));
});




