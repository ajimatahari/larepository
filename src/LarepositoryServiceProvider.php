<?php
declare(strict_types=1);

namespace Mola\Larepository;

use Illuminate\Console\DetectsApplicationNamespace;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Mola\Larepository\Console\Commands\InterfaceMakeCommand;
use Mola\Larepository\Console\Commands\RepositoryMakeCommand;

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

        $this->publishes(
            [
                self::$packageLocation . DIRECTORY_SEPARATOR . 'config'
                . DIRECTORY_SEPARATOR . 'repository.php' => config_path('repository.php')
            ],
            'config'
        );

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands(
                [
                    InterfaceMakeCommand::class,
                    RepositoryMakeCommand::class
                ]
            );
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
        $provider = app_path(
            'Providers' . DIRECTORY_SEPARATOR . RepositoryMakeCommand::$providerName . '.php'
        );

        // Register repository provider if created
        if ($files->exists($provider)) {
            $this->app->register(
                $this->getAppNamespace() . 'Providers\\' . RepositoryMakeCommand::$providerName
            );
        }
    }
}
