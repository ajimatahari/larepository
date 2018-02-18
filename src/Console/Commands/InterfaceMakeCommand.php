<?php

namespace Mola\Larepository\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Mola\Larepository\LarepositoryServiceProvider;

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
        if (str_contains($this->getNameInput(), config('repository.repository_path', 'Repositories'))) {
            return LarepositoryServiceProvider::$packageLocation . '/stubs/Repository/repository.interface.stub';
        }

        return LarepositoryServiceProvider::$packageLocation . '/stubs/Repository/interface.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . "\\" . config('repository.contracts_path', 'Contracts');
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

    /**
     * Replace the namespace for the given stub.
     *
     * @param  string $stub
     * @param  string $name
     * @return $this
     */
    protected function replaceNamespace(&$stub, $name)
    {
        parent::replaceNamespace($stub, $name);

        $stub = str_replace(
            [
                'DummyContractsRepositoryNamespace',
                'DummyContractsNamespace',
            ],
            [
                $this->getDefaultNamespace(rtrim($this->rootNamespace(), '\\')) . '\\' . config('repository.repository_path'),
                $this->getDefaultNamespace(rtrim($this->rootNamespace(), '\\'))
            ],
            $stub
        );

        return $this;
    }
}
