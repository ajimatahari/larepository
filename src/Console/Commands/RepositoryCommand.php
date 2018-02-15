<?php

namespace Mola\Larepository\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Mola\Larepository\LarepositoryServiceProvider;

class RepositoryCommand extends GeneratorCommand
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
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->hasOption('model') && !$this->files->exists($this->getPath($this->getModelNamespace()))) {
            $this->error('Model does not exist! Please provide correct namespace.');
            return;
        }

        if (parent::handle() === false) {
            return;
        }

        $this->call('make:interface', [
            'name' => 'Repositories\\' . $this->getNameInput(),
            '--crud' => $this->hasOption('model')
        ]);

        $this->createProviderBindings();
    }

    /**
     * Retrieves path of stub-file.
     *
     * @return string
     */
    protected function getStub(): string
    {
        if ($this->hasOption('model')) {
            return LarepositoryServiceProvider::$packageLocation . '/stubs/Repository/repository-crud.stub';
        }

        return LarepositoryServiceProvider::$packageLocation . '/stubs/Repository/repository.stub';
    }

    /**
     * Returns default namespace.
     *
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\\' . config('repository.repository_path', 'Repositories');
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
     * Replace the namespace for the given stub.
     *
     * @param  string $stub
     * @param  string $name
     * @return $this
     */
    protected function replaceNamespace(&$stub, $name)
    {
        $stub = str_replace(
            [
                'DummyNamespace',
                'DummyInterface',
                'InterfaceNamespaceDummy',
                'DummyRootNamespace',
                'DummyModelNamespace',
                'DummyModelClassName',
                'DummyModelVarName',
            ],
            [
                $this->getNamespace($name),
                $this->getClassFromNameInput($name) . 'Interface',
                $this->getInterfaceNamespace(),
                $this->rootNamespace(),
                $this->getModelNamespace(),
                $this->getModelClassName(),
                lcfirst($this->getModelClassName()) . 'Model',
            ],
            $stub
        );

        return $this;
    }

    /**
     * Returns the interface namespace.
     *
     * @return string
     */
    protected function getInterfaceNamespace(): string
    {
        return $this->rootNamespace()
            . config('repository.contracts_path', 'Contracts')
            . '\\' . config('repository.repository_path', 'Repositories')
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
            . config('repository.model_path', '')
            . "\\" . $this->option('model');
    }

    /**
     * Returns class-name of the provided model.
     *
     * @return string
     */
    private function getModelClassName(): string
    {
        // Build array of provided-namespace
        $modelClassName = explode('\\', $this->option('model'));

        // Return last entry with class-name
        return array_pop($modelClassName);
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
                'name' => 'RepositoryServiceProvider'
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
            . $this->getClassFromNameInput($this->getNameInput()) . "Interface::class => \\"
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
}
