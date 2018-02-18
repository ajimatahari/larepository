<?php

namespace Mola\Larepository\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Mola\Larepository\LarepositoryServiceProvider;

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
     * The name of the base-repository.
     *
     * @var string
     */
    public static $baseRepositoryName = 'BaseRepository';

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
     * Get the stub file for the base repository.
     *
     * @return string
     */
    protected function getBaseStub(): string
    {
        return LarepositoryServiceProvider::$packageLocation . '/stubs/Repository/repository-base.stub';
    }

    /**
     * Get the stub file for the base repository interface.
     *
     * @return string
     */
    protected function getBaseInterfaceStub(): string
    {
        return LarepositoryServiceProvider::$packageLocation . '/stubs/Repository/repository-base.interface.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . "\\" . $this->getRepositoryPath();
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
                'DummyBaseRepositoryNamespace',
                'DummyBaseRepository',
                'DummyModelNamespace'
            ],
            [
                $this->getClassFromNameInput($name) . 'Interface',
                $this->getInterfaceNamespace(),
                $this->getBaseRepositoryNamespace(),
                'BaseRepository',
                $this->getModelNamespace(),
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
        $specificNamespace = !empty($this->getNamespace($this->getNameInput())) ? '\\' . $this->getNamespace($this->getNameInput()) : '';

        return $this->rootNamespace()
            . $this->getContractPath()
            . '\\' . $this->getRepositoryPath()
            . $specificNamespace;
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
            . $this->option('model');
    }

    /**
     * Build the class with the given name.
     *
     * @param  string $name
     * @param string $stub
     * @return string
     */
    private function buildBaseRepo(string $name, string $stub): string
    {
        $stub = $this->files->get($stub);

        return $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->hasOption('model') || !$this->modelClassExists()) {
            $this->error('Model does not exist! Please provide correct namespace.');
            return;
        }

        if (parent::handle() === false) {
            return;
        }

        $this->call('make:interface', [
            'name' => 'Repositories\\' . $this->getNameInput()
        ]);

        if (!$this->baseRepoExists()) {
            $this->createBaseRepositoryClass();
            $this->createBaseRepositoryInterface();

            $this->info('Base-repository created successfully.');
        }

        // Add abstract and implementation to provider bindings
        $this->createProviderBindings();
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

    /**
     * Returns provider namespace.
     *
     * @return string
     */
    private function getProviderNamespace(): string
    {
        return $this->rootNamespace() . config('repository.provider_path', 'Providers');
    }

    /**
     * Returns name of new provider file.
     *
     * @return string
     */
    private function getProviderName(): string
    {
        return self::$providerName;
    }

    /**
     * Adds new entry to service providers bindings-array
     * or creates service provider with bindings-array.
     *
     * @return void
     */
    private function createProviderBindings()
    {
        $providerPath = $this->retrieveProviderPath();
        $newBindings = $this->buildBindingsString();

        if (!$this->files->exists($providerPath)) {
            // Crete new provider if not already exist
            $this->call('make:provider', [
                'name' => self::$providerName
            ]);

            // Place array with interface-bindings at beginning of class
            $needle = '{';
            $newEntry = "$needle\n    /**\n     * Repository interfaces and their implementation to bind.\n     *\n     * @var array\n     */\n    private \$repositoryBindings = [\n        $newBindings\n    ];\n";

            // Add loop to providers register-method to add bindings from array
            $registerLoop = "\t\tif (!empty(\$this->repositoryBindings)) {\n\t\t\tforeach(\$this->repositoryBindings as \$abstract => \$concrete) {\n\t\t\t\t\$this->app->bind(\$abstract, \$concrete);\n\t\t\t}\n\t\t}";
        } else {
            // Place new array entry at beginning of bindings-array
            $needle = 'repositoryBindings = [';
            $newEntry = "$needle\n        $newBindings,";
        }

        $provider = $this->files->get($providerPath);

        // Replace occurrence of needle in stringified provider
        $editedClass = str_replace_first($needle, $newEntry, $provider);

        if (!empty($registerLoop)) {
            $editedClass = str_replace_first(str_after($editedClass, "function register()\n"), "\t{\n$registerLoop\n\t}\n}", $editedClass);
        }

        // Place edited class at providers path
        $this->files->put($providerPath, $editedClass);
    }

    /**
     * Builds string of array entry with
     * interface/implementation-binding.
     *
     * @return string
     */
    private function buildBindingsString(): string
    {
        return '\\' . $this->getInterfaceNamespace()
            . '\\' . $this->getClassFromNameInput($this->getNameInput()) . "Interface::class => \\"
            . $this->getDefaultNamespace(str_replace('\\', '', $this->rootNamespace()))
            . '\\' . $this->getNameInput() . '::class';
    }

    /**
     * Returns path to provider location.
     *
     * @return string
     */
    private function retrieveProviderPath(): string
    {
        return $this->getPath($this->getProviderNamespace() . '\\' . $this->getProviderName());
    }

    /**
     * Checks if provided model-class exists.
     *
     * @return bool
     */
    private function modelClassExists(): bool
    {
        return $this->files->exists($this->getPath($this->getModelNamespace()));
    }

    /**
     * Build namespace for base repository.
     *
     * @return string
     */
    private function getBaseRepositoryNamespace(): string
    {
        return $this->rootNamespace() . $this->getRepositoryPath() . '\\' . self::$baseRepositoryName;
    }

    /**
     * Build namespace for base-repository interface.
     *
     * @return string
     */
    private function getBaseRepositoryInterfaceNamespace(): string
    {
        return $this->rootNamespace() . $this->getContractPath() . "\\" . $this->getRepositoryPath() . '\\' . self::$baseRepositoryName . 'Interface';
    }

    /**
     * Check if base-repository exists.
     *
     * @return bool
     */
    private function baseRepoExists(): bool
    {
        return $this->files->exists($this->getPath($this->getBaseRepositoryNamespace()));
    }

    /**
     * Creates the base-repo class-file.
     *
     * @return void
     */
    private function createBaseRepositoryClass(): void
    {
        $this->files->put($this->getPath($this->getBaseRepositoryNamespace()), $this->buildBaseRepo($this->getBaseRepositoryNamespace(), $this->getBaseStub()));
    }

    /**
     * Creates the base-repo interface-file.
     *
     * @return void
     */
    private function createBaseRepositoryInterface(): void
    {
        $this->files->put($this->getPath($this->getBaseRepositoryInterfaceNamespace()), $this->buildBaseRepo($this->getBaseRepositoryInterfaceNamespace(), $this->getBaseInterfaceStub()));
    }
}
