<?php
declare(strict_types=1);

namespace Mola\Larepository\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Mola\Larepository\File\BindingsUpdater;
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
                            {model : Name of the model to use in the repository}';

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
     * @var BindingsUpdater
     */
    protected $bindingsUpdater;

    public function __construct(Filesystem $filesystem, BindingsUpdater $bindingsUpdater)
    {
        parent::__construct($filesystem);

        $this->bindingsUpdater = $bindingsUpdater;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->hasArgument('model') || !$this->modelClassExists()) {
            $this->error('Model does not exist! Please provide correct namespace.');

            return;
        }

        if (parent::handle() === false) {
            return;
        }

        $this->call(
            'make:interface',
            [
                'name' => config('repository.repository_path', 'Repositories') . '\\' . $this->getNameInput()
            ]
        );

        $this->bindingsUpdater->createProviderBindings(
            $this->retrieveProviderPath(),
            $this->buildBindingsString()
        );
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        return LarepositoryServiceProvider::$packageLocation
            . DIRECTORY_SEPARATOR . 'stubs'
            . DIRECTORY_SEPARATOR . 'Repository'
            . DIRECTORY_SEPARATOR . 'repository.stub';
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
                'DummyModelNamespace'
            ],
            [
                $this->getClassFromNameInput($name) . 'Interface',
                $this->getInterfaceNamespace(),
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
        $specificNamespace = !empty($this->getNamespace($this->getNameInput()))
            ? '\\' . $this->getNamespace($this->getNameInput())
            : '';

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
            . '\\' . $this->argument('model');
    }

    /**
     * Returns repository path from config
     * or default value.
     *
     * @return string
     */
    private function getRepositoryPath()
    {
        return config('repository.repository_path', 'Repositories');
    }

    /**
     * Returns contracts path from config
     * or default value.
     *
     * @return string
     */
    private function getContractPath()
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
            . '\\' . $this->getNameInput() . '::class,';
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
}
