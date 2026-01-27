<?php

use Illuminate\Http\Client\Request;
use TJDFT\Laravel\Services\PolvoService;

test('Não obtém novo token se ainda estiver válido', function () {
    // Dado que existe um token na sessão
    cache()->put('polvo_token', 'fake-999');

    // Quando um novo token for solicitado
    new PolvoService()->getToken();

    // Então nenhuma requisição será realizada
    Http::assertNothingSent();
});

test('Obtém token se não houver um', function () {
    // Dado que não existe o token em cache
    cache()->forget('polvo_token');

    // Fake resposta do SSO
    Http::fake([
        config('services.polvo.auth_url') => [
            'access_token' => 'fake-123',
            'expires_in' => 300
        ]
    ]);

    // Quando um token for solicitado
    new PolvoService()->getToken();

    // Então as credenciais são enviadas corretamente para o SSO
    Http::assertSent(
        function (Request $request) {
            return
                $request->url() == config('tjdft.polvo.auth_url') &&
                $request['grant_type'] == 'client_credentials' &&
                $request['client_id'] == config('tjdft.polvo.client_id') &&
                $request['client_secret'] == config('tjdft.polvo.client_secret');
        }
    );

    // Então o token existirá na sessão
    expect(cache()->get('polvo_token'))->toBe('fake-123');
});




