<?php

// Auth
use Illuminate\Support\Facades\Route;
use TJDFT\Laravel\Http\Controllers\KeycloakController;

Route::middleware('web')->group(function () {
    Route::get('/login', [KeycloakController::class, 'login'])->name('login');
    Route::get('/auth/redirect/keycloak', [KeycloakController::class, 'redirect']);
    Route::get('/auth/callback/keycloak', [KeycloakController::class, 'callback']);
    Route::get('/auth/logout/keycloak', [KeycloakController::class, 'logout']);
});

Route::middleware(['web', 'auth'])->group(function () {
    // Seleção de perfil
    Route::livewire('/auth/perfil', 'tjdft::pages.perfil');

    // Permissions
    Route::livewire('/auth/permissions', 'tjdft::pages.permissions.index');
    Route::livewire('/auth/permissions/{user}', 'tjdft::pages.permissions.show');
});


