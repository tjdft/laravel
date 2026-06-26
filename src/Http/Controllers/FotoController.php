<?php

namespace TJDFT\Laravel\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

/**
 * O serviço de fotos está protegido atrás do Azure Proxy.
 * Portanto, qualquer chamada fora da rede interna falhará.
 * Este endpoint contorna o problema, fazendo a chamada diretamente do lado do servidor, que não requer autenticação.
 *
 * NOTA: Quando desenvolvendo em localhost é necessário ligar a VPN, pois não há outra forma de contornar.
 */
class FotoController extends Controller
{
    public function show(string $matricula)
    {
        $url = config('tjdft.fotos.url');
        $url = str($url)->replaceLast('/', '')->append("/{$matricula}.jpg")->toString();

        return response()->stream(function () use ($url) {
            echo Http::get($url)->body();
        }, 200, ['Content-Type' => 'image/jpeg', 'Cache-Control' => 'max-age=86400']);
    }
}
