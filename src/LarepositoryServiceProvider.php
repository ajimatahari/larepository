<?php

namespace Mola\Larepository;

use Illuminate\Console\DetectsApplicationNamespace;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Mola\Larepository\Console\Commands\InterfaceMakeCommand;
use Mola\Larepository\Console\Commands\RepositoryCommand;

class LarepositoryServiceProvider extends ServiceProvider
{
    use DetectsApplicationNamespace;

    /**
     * @var string
     */
    public static $packageLocation = '';
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        self::$packageLocation = dirname(__DIR__);

        $this->publishes([
            self::$packageLocation.'/config/repository.php' => config_path('repository.php')
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InterfaceMakeCommand::class,
                RepositoryCommand::class
            ]);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $files = $this->app->make(Filesystem::class);
        $provider = app_path('Providers/'.RepositoryCommand::$providerName.'.php');

        if ($files->exists($provider)) {
            $this->app->register($this->getAppNamespace().'Providers\\'.RepositoryCommand::$providerName);
        }
    }
}
