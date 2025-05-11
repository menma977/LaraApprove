<?php

namespace Menma977\Larapprove;

use Illuminate\Support\ServiceProvider;

class LaraApproveServiceProvider extends ServiceProvider
{
    /**
     * @noinspection PhpUnused
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/Migrations' => database_path('migrations')], 'larapprove-migrations');
            $this->publishes([__DIR__.'/Models' => base_path('app/Models')], 'larapprove-models');
            $this->publishes([__DIR__.'/Services' => base_path('app/Services')], 'larapprove-services');
            $this->publishes([__DIR__.'/configs' => config_path()], 'larapprove-config');

            $this->publishes([
                __DIR__.'/Migrations' => database_path('migrations'),
                __DIR__.'/configs' => config_path(),
            ], 'larapprove');
        }
    }
}
