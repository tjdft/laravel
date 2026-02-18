<?php

namespace TJDFT\Laravel\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Configuration\Exceptions as BaseExceptions;
use Illuminate\Http\Request;
use Mary\Exceptions\ToastException;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use function Sentry\init;

class ExceptionHandler
{
    public static function register(BaseExceptions $exceptions): void
    {
        // Sentry
        init(['dsn' => config('tjdft.sentry.dsn')]);
        Integration::handles($exceptions);

        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            // Deixa o Laravel tratar de forma padrão esses casos
            if (config('app.debug') || $e instanceof AuthenticationException || $e instanceof ToastException) {
                return $response;
            }

            // Para todos os outros caso renderizamos uma view de erro personalizada
            $status = $response->getStatusCode();

            return response()->view('tjdft::erro', [
                'isLivewire' => $request->hasHeader('X-Livewire'),
                'detail' => $e->getMessage(),
                'status' => $status,
            ], status: $status);
        });
    }
}
