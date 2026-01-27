<?php

test('Redireciona para Keycloak se não estiver logado', function () {
    // Dado que eu não estava logado
    $this->logout();

    // Quando eu acessar a rota de login
    // Então devo ser redirecionado para o Keycloak
    $this->get('/login')->assertRedirect('/auth/redirect/keycloak');

    $this->get('/auth/redirect/keycloak')->assertRedirectContains(config('tjdft.keycloak.base_url'));
});

test('Redireciona para a home se ja restive logado', function () {
    // Dado que eu já estou logado
    // Quando eu acessar a rota de login
    // Então devo ser redirecionado para a home
    $this->get('/login')->assertRedirect('/');
});

test('Redireciona para a home se já estiver logado apos callback do Keycloak', function () {
    // Dado que eu já estou logado
    // Quando eu acessar a rota de callback do Keycloak
    // Então devo ser redirecionado para a home
    $this->get('/auth/callback/keycloak')->assertRedirect('/');
});

test('Redireciona para o login ao tentar acessar rotas protegidas', function () {
    // Dado que eu não estava logado
    $this->logout();

    // Quando eu tentar acessar uma rota protegida
    // Então devo ser redirecionado para o login
    $this->get('/dashboard/index')->assertRedirect('/login');
});

test('Redirecionamento com Livewire', function () {
    // Dado que eu não estava logado
    $this->logout();

    // Quando eu tentar acessar uma rota protegida
    // Então devo ser redirecionado para o login
    $this->get('/login', ['Sec-Fetch-Mode' => 'cors'])->assertSee('Autenticando ...');
});

test('Redireciona para Keycloak ao fazer logout', function () {
    // Quando eu acessar a rota de logout
    // Então devo ser redirecionado para o Keycloak
    $this->get("/auth/logout/keycloak")->assertRedirectContains(config('tjdft.keycloak.base_url'));
});


