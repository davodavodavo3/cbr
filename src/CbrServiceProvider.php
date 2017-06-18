<?php

namespace Scorpion\Cbr;

use Illuminate\Support\ServiceProvider;

class CurrencyServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCurrency();

        if ($this->app->runningInConsole()) {
            $this->registerResources();
            $this->registerCurrencyCommands();
        }
    }

    /**
     * Register cbr provider.
     *
     * @return void
     */
    public function registerCurrency()
    {
        $this->app->singleton('cbr', function ($app) {
            return new Cbr(
                $app->config->get('cbr', []),
                $app['cache']
            );
        });
    }

    /**
     * Register cbr resources.
     *
     * @return void
     */
    public function registerResources()
    {
        if ($this->isLumen() === false) {
            $this->publishes([
                __DIR__ . '/Config/cbr.php' => config_path('cbr.php'),
            ], 'config');

            $this->mergeConfigFrom(
                __DIR__ . '/Config/cbr.php', 'cbr'
            );
        }

        $this->publishes([
            __DIR__ . '/Database/migrations' => base_path('/database/migrations'),
        ], 'migrations');
    }

    /**
     * Register currency commands.
     *
     * @return void
     */
    public function registerCurrencyCommands()
    {
        $this->commands([
            Console\Cleanup::class,
            Console\Manage::class,
            Console\Update::class,
        ]);
    }

    /**
     * Check if package is running under Lumen app
     *
     * @return bool
     */
    protected function isLumen()
    {
        return str_contains($this->app->version(), 'Lumen') === true;
    }
}