<?php

// Auth
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use TJDFT\Laravel\Http\Controllers\KeycloakController;

Route::middleware('web')->group(function () {
    Route::get('/login', [KeycloakController::class, 'login'])->name('login');
    Route::get('/auth/redirect/keycloak', [KeycloakController::class, 'redirect']);
    Route::get('/auth/callback/keycloak', [KeycloakController::class, 'callback']);
    Route::get('/auth/logout/keycloak', [KeycloakController::class, 'logout']);
});

Route::middleware(['web', 'auth'])->group(function () {
    // Seleção de perfil
    Volt::route('/auth/perfil', 'perfil');

    // Permissions
    Volt::route('/auth/permissions', 'permissions.index');
    Volt::route('/auth/permissions/{user}', 'permissions.show');
});


