<?php

namespace Mola\Larepository\Tests;


use Illuminate\Support\Facades\Config;
use Mola\Larepository\LarepositoryServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('repository.repository_path', getenv('REPOSITORY_PATH'));
        $app['config']->set('repository.model_path', getenv('MODEL_PATH'));
        $app['config']->set('repository.contracts_path', getenv('CONTRACTS_PATH'));
        $app['config']->set('repository.provider_path', getenv('PROVIDER_PATH'));
    }

    /**
     * Sets package providers for test-case.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [LarepositoryServiceProvider::class];
    }
}