<?php

namespace Mola\Larepository;

use Illuminate\Support\ServiceProvider;
use Mola\Larepository\Console\Commands\InterfaceMakeCommand;
use Mola\Larepository\Console\Commands\RepositoryCommand;

class LarepositoryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            dirname(__DIR__).'/config/repository.php' => config_path('repository.php')
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            InterfaceMakeCommand::class,
            RepositoryCommand::class
        ]);
    }
}
