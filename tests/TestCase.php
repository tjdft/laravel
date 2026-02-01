<?php

namespace TJDFT\Laravel\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Support\Facades\DB;
use Livewire\LivewireServiceProvider;
use Mary\MaryServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use SocialiteProviders\Manager\ServiceProvider as SocialiteKeycloakServiceProvider;
use Technikermathe\LucideIcons\BladeLucideIconsServiceProvider;
use TJDFT\Laravel\TJDFTLaravelServiceProvider;
use TJDFT\Laravel\Traits\TestUtis;
use Workbench\App\Actions\AtualizarPermissionsLoginAction;
use function Orchestra\Testbench\workbench_path;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use WithWorkbench, TestUtis;

    protected function boot(): void
    {
        $this->setupSqliteCompatibility();
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../workbench/database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeLucideIconsServiceProvider::class,
            MaryServiceProvider::class,
            SocialiteKeycloakServiceProvider::class,
            TJDFTLaravelServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Configurações do Polvo
        config()->set('tjdft.polvo.api_url', 'https://polvo.com/graphql');
        config()->set('tjdft.polvo.auth_url', 'https://polvo.com/auth/realms/COMPANY/protocol/openid-connect/token');
        config()->set('tjdft.polvo.cache_ttl', '1 hour');

        // Configurações do Keycloak
        config()->set('tjdft.keycloak.base_url', 'https://keycloak.com/auth');
        config()->set('tjdft.keycloak.client_id', 'exemplo');
        config()->set('tjdft.keycloak.client_secret', 'exemplo');
        config()->set('tjdft.keycloak.redirect', '/abc/test');

        // Permssions action
        config()->set('tjdft.permissions_action', AtualizarPermissionsLoginAction::class);

        // GraphQL Faker
        config()->set('tjdft.graphql_faker.schema_path', '/../../../../../laravel/workbench/tests/faker.graphql');
        config()->set('tjdft.graphql_faker.schema_path_overrides', '/../../../../../laravel/workbench/tests/faker.graphql.php');

        // Namespace de views para `layouts` do Livewire
        $app['view']->addNamespace('layouts', workbench_path('resources/views/layouts'));
    }

    // Registra funções e sobrescreve gramática para compatibilidade com SQLite em testes
    private function setupSqliteCompatibility(): void
    {
        $connection = DB::connection();
        $pdo = $connection->getPdo();

        // Registra a função `unaccent`
        $pdo->sqliteCreateFunction('unaccent', fn($value) => str($value)->ascii());

        // Registra a função `immutable_unaccent`
        $pdo->sqliteCreateFunction('immutable_unaccent', fn($value) => str($value)->ascii());

        // Substitui `ILIKE` por `LIKE` e remove `::text` da query
        $connection->setQueryGrammar(new class($connection) extends SQLiteGrammar {
            public function compileSelect($query)
            {
                return str(parent::compileSelect($query))
                    ->replace('::text', '')
                    ->replace('ILIKE', 'LIKE')
                    ->toString();
            }
        });
    }
}
