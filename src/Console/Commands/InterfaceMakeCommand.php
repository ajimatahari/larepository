<?php

namespace Mola\Larepository\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Mola\Larepository\LarepositoryServiceProvider;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class InterfaceMakeCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:interface
                            {name : The name of the new interface}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new interface';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Interface';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        return LarepositoryServiceProvider::$packageLocation . '/stubs/Repository/repository.interface.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\\' . config('repository.contracts_path', 'Contracts');
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput(): string
    {
        return trim($this->argument('name')) . $this->type;
    }
}
