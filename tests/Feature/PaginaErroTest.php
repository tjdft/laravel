<?php

use Illuminate\Auth\AuthenticationException;
use Mary\Exceptions\ToastException;

test('Mostra página de erro quando há erro fatal', function () {
    // Quando eu acessar uma página que gera um erro fatal
    // Então eu devo ver a página de erro
    $this->get('/pagina-com-erro-fatal')
        ->assertViewIs('tjdft::erro')
        ->assertSee('erro fatal!')
        ->assertServerError();
});

test('Mostra página de erro quando há erro de permissão', function () {
    // Quando eu acessar uma página que gera um erro de permissão
    // Então eu devo ver a página de erro
    $this->get('/pagina-com-erro-permissao')
        ->assertViewIs('tjdft::erro')
        ->assertForbidden();
});

test('Não mostra página de erro em modo debug', function () {
    $this->withoutExceptionHandling();

    // Dado que o aplicativo está em modo debug
    config(['app.debug' => true]);

    // Quando eu acessar uma página que gera um erro fatal
    // Então eu não devo ver a página de erro
    $this->get('/pagina-com-erro-fatal')
        ->assertViewMissing('tjdft::erro')
        ->assertSee('erro fatal!')
        ->assertServerError();
})->throws(Exception::class, 'erro fatal!');

test('Não mostra página de erro para AuthenticationException', function () {
    $this->withoutExceptionHandling();

    // Quando eu acessar uma página que gera uma AuthenticationException
    // Então eu não devo ver a página de erro
    $this->get('/pagina-com-erro-autenticacao')
        ->assertViewMissing('tjdft::erro')
        ->assertUnauthorized();
})->throws(AuthenticationException::class);

test('Não mostra página de erro para ToastException', function () {
    $this->withoutExceptionHandling();

    // Quando eu acessar uma página que gera uma ToastException
    // Então eu não devo ver a página de erro
    $this->get('/pagina-com-erro-toast')
        ->assertViewMissing('tjdft::erro')
        ->assertSee('Erro de toast!');
})->throws(ToastException::class, 'Erro de toast!');
