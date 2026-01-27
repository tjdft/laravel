<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Route;
use Mary\Exceptions\ToastException;

Route::middleware('auth')->group(function () {
    Route::get('/dashboard/index', function () {
        return 'Dashboard teste';
    });

    Route::get('/pagina-com-erro-fatal', function () {
        throw new Exception("erro fatal!");
    });

    Route::get('/pagina-com-erro-permissao', function () {
        auth()->user()->authorize('permissao ninja que ninguém tem');
    });

    Route::get('/pagina-com-erro-autenticacao', function () {
        throw new AuthenticationException();
    });

    Route::get('/pagina-com-erro-toast', function () {
        throw new ToastException('Erro de toast!');
    });
});
