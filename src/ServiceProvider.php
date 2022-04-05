<?php

namespace Tal7aouy\Laraversion;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot()
    {
        \config('versionable.migrations') && $this->loadMigrationsFrom(__DIR__ . '/../migrations');

        $this->publishes([
            __DIR__ . '/../migrations' => \database_path('migrations'),
        ], 'migrations');

        $this->publishes([
            __DIR__ . '/../config/versionable.php' => \config_path('versionable.php'),
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/versionable.php',
            'versionable'
        );
    }
}
