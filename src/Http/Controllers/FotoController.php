<?php

namespace TJDFT\Laravel\Http\Controllers;

use Illuminate\Support\Facades\Http;

/**
 * Serve as fotos dos usuários por meio de um endpoint interno.
 * Ex: /tjdft/fotos/123456
 *
 * Esta requisição ocorre do lado do servidor, contornando o problema de proxy.
 * NOTA: Em localhost é necessário ligar a VPN para simular a rede interna.
 */
class FotoController
{
    public function show(string $matricula)
    {
        $url = str(config('tjdft.fotos.url'))->finish("/")->append("{$matricula}.jpg");

        return response()->stream(function () use ($url) {
            echo Http::get($url)->body();
        }, 200, ['Content-Type' => 'image/jpeg', 'Cache-Control' => 'max-age=86400']);
    }
}
