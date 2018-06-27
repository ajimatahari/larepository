<?php
declare(strict_types=1);

namespace Mola\Larepository\File;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Mola\Larepository\Console\Commands\RepositoryMakeCommand;

class BindingsUpdater
{
    /**
     * @var string
     */
    protected $arrayNeedle = '';

    /**
     * @var string
     */
    protected $newEntry = '';

    /**
     * @var string
     */
    protected $registerLoop = '';

    /**
     * @var string
     */
    protected $fieldName = '';

    /**
     * @var Filesystem
     */
    protected $fileSystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->fieldName = (string)config('bindings_array_name', 'repositoryBindings');
        $this->arrayNeedle = $this->fieldName . ' = [';
        $this->fileSystem = $filesystem;
    }

    /**
     * Adds new entry to service providers bindings-array
     * or creates service provider with bindings-array.
     *
     * @param string $providerPath
     * @param string $bindings
     * @return bool
     */
    public function createProviderBindings(string $providerPath, string $bindings): bool
    {
        if ($providerPath === '' || $bindings === '') {
            return false;
        }

        $isNew = false;
        $this->newEntry = "{$this->arrayNeedle}\n     {$bindings}";

        if (!$this->fileSystem->exists($providerPath)) {
            Artisan::call(
                'make:provider',
                [
                    'name' => RepositoryMakeCommand::$providerName
                ]
            );

            $isNew = true;
            $this->setParametersForNewProvider($bindings);
        }

        $provider = $this->fileSystem->get($providerPath);

        // Replace occurrence of needle in stringified provider
        $editedProvider = str_replace_first($this->arrayNeedle, $this->newEntry, $provider);

        if ($isNew) {
            $editedProvider = str_replace_first(
                str_after($editedProvider, "function register()\n"),
                "\t{\n$this->registerLoop\n\t}\n}",
                $editedProvider
            );
        }

        // Place edited class at providers path
        return $this->fileSystem->put($providerPath, $editedProvider) > 0;
    }

    protected function getArrayTemplateForNewProvider(string $bindings): string
    {
        return <<<EOT
{$this->arrayNeedle}
    /**
    * Repository interfaces and their implementation to bind.
    *
    * @var array
    */
    protected \${$this->fieldName} = [
        {$bindings}
    ];

EOT;
    }

    protected function getRegisterLoopTemplate(): string
    {
        return <<<EOT
        if (!empty(\$this->{$this->fieldName})) {
            foreach(\$this->{$this->fieldName} as \$abstract => \$concrete) {
                \$this->app->bind(\$abstract, \$concrete);
            }
        }
EOT;
    }

    protected function setParametersForNewProvider(string $bindings): void
    {
        $this->arrayNeedle = '{';
        $this->newEntry = $this->getArrayTemplateForNewProvider($bindings);
        $this->registerLoop = $this->getRegisterLoopTemplate();
    }
}