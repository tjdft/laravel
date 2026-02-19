<?php

return [

    /**
     * A classe invocada antes de finalizar o processo de login.
     * Utilize-a para definir a lógica de concessão dinâmica de roles do usuário.
     */
    'permissions_action' => env('TJDFT_PERMISSIONS_ACTION', 'App\Actions\AtualizarPermissionsLoginAction'),

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

    // SENTRY
    'sentry' => [
        'dsn' => env('TJDFT_SENTRY_LARAVEL_DSN'),
    ],

    // SMAX
    'smax' => [
        'url' => env('TJDFT_SMAX_URL'),
        'tenant_id' => env('TJDFT_SMAX_TENANT_ID'),
        'requests_offering' => env('TJDFT_SMAX_REQUESTS_OFFERING'),
        'auth_login' => env('TJDFT_SMAX_LOGIN'),
        'auth_password' => env('TJDFT_SMAX_PASSWORD'),
        'api_url' => env('TJDFT_SMAX_URL') . "/rest/" . env('TJDFT_SMAX_TENANT_ID') . "/ems",
        'auth_url' => env('TJDFT_SMAX_URL') . '/auth/authentication-endpoint/authenticate/login?TENANTID=' . env('TJDFT_SMAX_TENANT_ID'),
        'fallback_emails' => env('TJDFT_SMAX_FALLBACK_EMAILS'),
    ],

    // KEYCLOAK
    'keycloak' => [
        'client_id' => env('TJDFT_KEYCLOAK_CLIENT_ID'),
        'client_secret' => env('TJDFT_KEYCLOAK_CLIENT_SECRET'),
        'redirect' => env('TJDFT_KEYCLOAK_REDIRECT_URI', '/auth/callback/keycloak'),
        'base_url' => env('TJDFT_KEYCLOAK_BASE_URL'),
        'realms' => env('TJDFT_KEYCLOAK_REALMS')
    ],

    // API RH
    'polvo' => [
        'api_url' => env('TJDFT_POLVO_API_URL'),
        'auth_url' => env('TJDFT_POLVO_AUTH_URL'),
        'client_id' => env('TJDFT_POLVO_CLIENT_ID'),
        'client_secret' => env('TJDFT_POLVO_CLIENT_SECRET'),
        'cache_ttl' => env('TJDFT_POLVO_CACHE_TTL', '1 hour'),
    ],

    // GRAPHQL FAKER
    'graphql_faker' => [
        'schema_path' => env('TJDFT_GRAPHQL_FAKER_SCHEMA_PATH', 'tests/faker.graphql'),
        'schema_path_overrides' => env('TJDFT_GRAPHQL_FAKER_SCHEMA_PATH_OVERRIDES', 'tests/faker.graphql.php'),
    ],
];
