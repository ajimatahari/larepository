<?php

namespace Mola\Larepository\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Mola\Larepository\LarepositoryServiceProvider;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class RepositoryMakeCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:repository 
                            {name : The name of the new repository}
                            {--model= : Name of the model to use in the repository}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new repository.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Repository';

    /**
     * The name of the repository provider.
     *
     * @var string
     */
    public static $providerName = 'RepositoryServiceProvider';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        return LarepositoryServiceProvider::$packageLocation . '/stubs/Repository/repository.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\\' . $this->getRepositoryPath();
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
                'DummyInterface',
                'InterfaceNamespaceDummy',
            ],
            [
                $this->getClassFromNameInput($name) . 'Interface',
                $this->getInterfaceNamespace(),
            ],
            $stub
        );

        return $this;
    }

    /**
     * Returns class-name from provided input.
     *
     * @param $name
     * @return string
     */
    protected function getClassFromNameInput($name): string
    {
        return str_replace($this->getNamespace($name) . '\\', '', $name);
    }

    /**
     * Returns the interface namespace.
     *
     * @return string
     */
    protected function getInterfaceNamespace(): string
    {
        return $this->rootNamespace()
            . $this->getContractPath()
            . '\\' . $this->getRepositoryPath()
            . '\\' . $this->getNamespace($this->getNameInput());
    }

    /**
     * Returns namespace of provided model.
     *
     * @return string
     */
    private function getModelNamespace(): string
    {
        return $this->rootNamespace()
            . $this->getModelPath()
            . "\\" . $this->option('model');
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->hasOption('model') || !$this->files->exists($this->getPath($this->getModelNamespace()))) {
            $this->error('Model does not exist! Please provide correct namespace.');
            return;
        }

        if (parent::handle() === false) {
            return;
        }

        if (!$this->files->exists($this->getPath($this->getDefaultNamespace($this->rootNamespace())))) {

        }

        $this->call('make:interface', [
            'name' => 'Repositories\\' . $this->getNameInput()
        ]);
    }

    /**
     * Returns repository path from config
     * or default value.
     *
     * @return string
     */
    private function getRepositoryPath(): string
    {
        return config('repository.repository_path', 'Repositories');
    }

    /**
     * Returns contracts path from config
     * or default value.
     *
     * @return string
     */
    private function getContractPath(): string
    {
        return config('repository.contracts_path', 'Contracts');
    }

    /**
     * Returns model path from config
     * or default value.
     *
     * @return string
     */
    private function getModelPath()
    {
        return config('repository.model_path', '');
    }
}
