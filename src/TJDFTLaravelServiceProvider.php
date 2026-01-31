<?php

namespace TJDFT\Laravel;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Number;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use SocialiteProviders\Keycloak\Provider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class TJDFTLaravelServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Rotas
        $this->loadroutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadroutesFrom(__DIR__ . '/../routes/api.php');

        // Configuração
        $this->mergeConfigFrom(__DIR__ . '/../config/tjdft.php', 'tjdft');

        // Views blade
        $this->loadViewsFrom(__DIR__ . '/../resources/views/blade', 'tjdft');

        // Traduções pt-BR
        $this->loadTranslationsFrom(__DIR__ . '/../lang');
        $this->loadJsonTranslationsFrom(__DIR__ . '/../lang');

        // Encaminha as configurações do Keycloak para o pacote `socialiteproviders/keycloak`
        config()->set('services.keycloak', config('tjdft.keycloak'));

        // Encaminha as configurações do Sentry para o pacote `sentry/sentry-laravel`
        config()->set('sentry.dsn', config('tjdft.sentry.dsn'));

        // Carrega os componentes do Livewire
        Livewire::addNamespace('tjdft', __DIR__ . '/../resources/views/pages');

        // Socialite - Keycloak
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('keycloak', Provider::class);
        });

        // Migrations
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }

    public function register(): void
    {
        // Proíbe comandos destrutivos em produção
        DB::prohibitDestructiveCommands($this->app->isProduction());

        // Moeda
        Number::useLocale('pt-BR');
        Number::useCurrency('BRL');

        // now() + json_encode()
        Date::serializeUsing(function ($date) {
            return $date->format('Y-m-d H:i:s');
        });
    }
}
