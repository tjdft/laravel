<?php

namespace TJDFT\Laravel;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Number;
use Illuminate\Support\ServiceProvider;
use Livewire\Volt\Volt;
use SocialiteProviders\Keycloak\Provider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class LaravelServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadroutesFrom(__DIR__ . '/../routes/web.php');
        $this->mergeConfigFrom(__DIR__ . '/../config/tjdft.php', 'tjdft');
        $this->loadTranslationsFrom(__DIR__ . '/../lang');
        $this->loadJsonTranslationsFrom(__DIR__ . '/../lang');

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }

        // Passa as configurações de Keycloak deste pacote para o pacote `socialiteproviders/keycloak`
        if (! config('services.keycloak')) {
            config()->set('services.keycloak', config('tjdft.keycloak'));
        }
    }

    public function register()
    {
        // Carrega os componentes do Livewire Volt
        Volt::mount([__DIR__ . '/../resources/views/livewire']);

        // Proíbe comandos destrutivos em produção
        DB::prohibitDestructiveCommands($this->app->isProduction());

        // Moeda
        Number::useLocale('pt-BR');
        Number::useCurrency('BRL');

        // now() + json_encode()
        Date::serializeUsing(function ($date) {
            return $date->format('Y-m-d H:i:s');
        });

        // Socialite - Keycloak
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('keycloak', Provider::class);
        });
    }
}
