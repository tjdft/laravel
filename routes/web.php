<?php

use Illuminate\Support\Facades\Route;
use TJDFT\Laravel\Http\Controllers\FotoController;
use TJDFT\Laravel\Http\Controllers\KeycloakController;

Route::middleware('web')->group(function () {
    // Auth
    Route::get('/login', [KeycloakController::class, 'login'])->name('login');
    Route::get('/auth/redirect/keycloak', [KeycloakController::class, 'redirect']);
    Route::get('/auth/callback/keycloak', [KeycloakController::class, 'callback']);
    Route::get('/auth/logout/keycloak', [KeycloakController::class, 'logout']);

    // Fotos
    Route::get('/tjdft/fotos/{matricula}', [FotoController::class, 'show']);

    // Imagem padrão do usuário
    Route::get('/user.png', function () {
        return response()->file(__DIR__ . '/../public/user.png')->setCache(['max_age' => 86400, 'public' => true]);
    });

    // Imagem Impersonate
    Route::get('/impersonate.png', function () {
        return response()->file(__DIR__ . '/../public/impersonate.png')->setCache(['max_age' => 86400, 'public' => true]);
    });
});

Route::middleware(['web', 'auth'])->group(function () {
    // Seleção de perfil
    Route::livewire('/auth/perfil', 'tjdft::perfil');

    // Permissions
    Route::livewire('/auth/permissions', 'tjdft::permissions.index');
    Route::livewire('/auth/permissions/{user}', 'tjdft::permissions.show');

    // Impersonate
    Route::livewire('/auth/impersonate', 'tjdft::impersonate');
});


