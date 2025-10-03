<?php

return [

    /**
     * A classe invocada antes de finalizar o processo de login.
     * Utilize-a para definir a lógica de concessão dinâmica de roles do usuário.
     */
    'permissions_action' => env('TJDFT_PERMISSION_ACTION', 'App\Actions\AtualizarPermissionsLoginAction'),

    /**
     * Nome do schema onde deverá ser criada as extensões do PostgreSQL.
     */
    'pgsql_extensions' => [
        'schema' => env('TJDFT_PGSQL_EXTENSIONS_SCHEMA', '')
    ],

    // ACL
    'acl' => [
        'tables' => [
            'roles' => env('TJDFT_ACL_TABLES_ROLES', 'acl_roles'),
            'permissions' => env('TJDFT_ACL_TABLES_ROLES', 'acl_permissions'),
            'grants' => env('TJDFT_ACL_TABLES_ROLES', 'acl_grants'),
        ]
    ],

    // API RH
    'polvo' => [
        'api_url' => env('TJDFT_POLVO_API_URL'),
        'auth_url' => env('TJDFT_POLVO_AUTH_URL'),
        'client_id' => env('TJDFT_POLVO_CLIENT_ID'),
        'client_secret' => env('TJDFT_POLVO_CLIENT_SECRET'),
    ],

    // KEYCLOAK
    'keycloak' => [
        'client_id' => env('TJDFT_KEYCLOAK_CLIENT_ID'),
        'client_secret' => env('TJDFT_KEYCLOAK_CLIENT_SECRET'),
        'redirect' => env('TJDFT_KEYCLOAK_REDIRECT_URI', '/auth/callback/keycloak'),
        'base_url' => env('TJDFT_KEYCLOAK_BASE_URL'),
        'realms' => env('TJDFT_KEYCLOAK_REALMS')
    ],
];
