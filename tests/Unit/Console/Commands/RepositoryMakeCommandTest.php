<?php

namespace Mola\Larepository\Tests\Unit\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Mola\Larepository\Console\Commands\RepositoryMakeCommand;
use Mola\Larepository\LarepositoryServiceProvider;
use Mola\Larepository\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\Argument;
use Prophecy\Prophecy\ProphecyInterface;
use Symfony\Component\Console\Input\InputInterface;

class RepositoryMakeCommandTest extends TestCase
{
    /**
     * @var RepositoryMakeCommand|MockObject
     */
    protected $subject;
    /**
     * @var InputInterface|ProphecyInterface
     */
    protected $inputMock;
    /**
     * @var Filesystem
     */
    private $filesMock;

    public function setUp()
    {
        parent::setUp();

        $this->filesMock = $this->prophesize(Filesystem::class);

        $this->subject = $this->getMockBuilder(RepositoryMakeCommand::class)
            ->setConstructorArgs([$this->filesMock->reveal()])
            ->setMethods(['call', 'error', 'info'])
            ->getMock();

        $this->inputMock = $this->prophesize(InputInterface::class);

        $this->subject->setLaravel($this->app);

        $reflectionClass = new \ReflectionClass($this->subject);

        $reflectionProperty = $reflectionClass->getProperty('input');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->subject, $this->inputMock->reveal());
    }

    public function tearDown()
    {
        parent::tearDown();

        unset($this->filesMock);
    }

    /**
     * @test
     */
    public function handleShouldReturnErrorMessageIfProvidedModelDoesNotExist()
    {
        $this->inputMock
            ->hasOption('model')
            ->shouldBeCalled()
            ->willReturn(true);
        $this->inputMock
            ->getOption('model')
            ->shouldBeCalled();
        $this->filesMock
            ->exists(Argument::containingString($this->app->path()))
            ->shouldBeCalled()
            ->willReturn(false);

        $this->subject
            ->expects($this->once())
            ->method('error')
            ->with('Model does not exist! Please provide correct namespace.');

        $this->subject->handle();
    }

    /**
     * @test
     */
    public function handleShouldReturnErrorMessageIfModelIsNotProvided()
    {
        $this->inputMock
            ->hasOption('model')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->subject
            ->expects($this->once())
            ->method('error')
            ->with('Model does not exist! Please provide correct namespace.');

        $this->subject->handle();
    }

    /**
     * @test
     */
    public function handleShouldReturnIfParentHandleReturnsFalse()
    {
        $this->inputMock
            ->hasOption('model')
            ->shouldBeCalled()
            ->willReturn(true);
        $this->inputMock
            ->getOption('model')
            ->shouldBeCalled();
        $this->filesMock
            ->exists(Argument::containingString($this->app->path()))
            ->shouldBeCalledTimes(2)
            ->willReturn(true, true);
        $this->inputMock
            ->getArgument('name')
            ->shouldBeCalled()
            ->willReturn('Foo');

        $this->subject
            ->expects($this->once())
            ->method('error')
            ->with('Repository already exists!');

        $this->subject->handle();
    }

    /**
     * @test
     */
    public function handleShouldCreateInterfaceWithProvidedName()
    {
        $this->inputMock
            ->hasOption('model')
            ->shouldBeCalled()
            ->willReturn(true);
        $this->inputMock
            ->getOption('model')
            ->shouldBeCalled();
        $this->filesMock
            ->exists(Argument::containingString($this->app->path()))
            ->shouldBeCalledTimes(4)
            ->willReturn(true, false, true, true);
        $this->inputMock
            ->getArgument('name')
            ->shouldBeCalled()
            ->willReturn('FooNamespace\\Foo');
        $this->filesMock
            ->isDirectory(Argument::containingString($this->app->path()))
            ->shouldBeCalled()
            ->willReturn(true);
        $this->filesMock
            ->put(Argument::containingString('FooRepository.php'), 'stub')
            ->shouldBeCalled();
        $this->filesMock
            ->get(LarepositoryServiceProvider::$packageLocation . '/stubs/Repository/repository.stub')
            ->shouldBeCalled()
            ->willReturn('stub');
        $this->filesMock
            ->get(Argument::containingString($this->app->path() . '/' . config('repository.provider_path', 'Providers') . '/' . RepositoryMakeCommand::$providerName . '.php'))
            ->shouldBeCalled()
            ->willReturn('service provider');
        $this->filesMock
            ->put(Argument::containingString($this->app->path() . '/' . config('repository.provider_path', 'Providers') . '/' . RepositoryMakeCommand::$providerName . '.php'), 'service provider')
            ->shouldBeCalled();

        $this->subject
            ->expects($this->once())
            ->method('info')
            ->with('Repository created successfully.');
        $this->subject
            ->expects($this->once())
            ->method('call')
            ->with('make:interface', [
                'name' => 'Repositories\\FooNamespace\\FooRepository'
            ]);

        $this->subject->handle();
    }

    /**
     * @test
     */
    public function handleShouldCreateBaseRepositoryAndInterfaceIfNotExist()
    {
        $this->inputMock
            ->hasOption('model')
            ->shouldBeCalled()
            ->willReturn(true);
        $this->inputMock
            ->getOption('model')
            ->shouldBeCalled();
        $this->filesMock
            ->exists(Argument::containingString($this->app->path()))
            ->shouldBeCalledTimes(4)
            ->willReturn(true, false, false, true);
        $this->inputMock
            ->getArgument('name')
            ->shouldBeCalled()
            ->willReturn('Foo');
        $this->filesMock
            ->isDirectory(Argument::containingString($this->app->path()))
            ->shouldBeCalled()
            ->willReturn(true);
        $this->filesMock
            ->get(LarepositoryServiceProvider::$packageLocation . '/stubs/Repository/repository.stub')
            ->shouldBeCalled()
            ->willReturn('stub');
        $this->filesMock
            ->put(Argument::containingString($this->app->path()), 'stub')
            ->shouldBeCalledTimes(3);
        $this->filesMock
            ->get(LarepositoryServiceProvider::$packageLocation . '/stubs/Repository/repository-base.stub')
            ->shouldBeCalled()
            ->willReturn('stub');
        $this->filesMock
            ->get(LarepositoryServiceProvider::$packageLocation . '/stubs/Repository/repository-base.interface.stub')
            ->shouldBeCalled()
            ->willReturn('stub');
        $this->filesMock
            ->get(Argument::containingString($this->app->path() . '/' . config('repository.provider_path') . '/' . RepositoryMakeCommand::$providerName))
            ->shouldBeCalled()
            ->willReturn('* @var array');
        $this->filesMock
            ->put(Argument::containingString($this->app->path() . '/' . config('repository.provider_path') . '/' . RepositoryMakeCommand::$providerName), Argument::containingString('* @var array'))
            ->shouldBeCalled();

        $this->subject
            ->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Repository created successfully.'],
                ['Base-repository created successfully.']
            );
        $this->subject
            ->expects($this->once())
            ->method('call')
            ->with('make:interface', [
                'name' => 'Repositories\\FooRepository'
            ]);

        $this->subject->handle();
    }

    /**
     * @test
     */
    public function handleShouldAddBindingsToServiceProviderArray()
    {
        $this->inputMock
            ->hasOption('model')
            ->shouldBeCalled()
            ->willReturn(true);
        $this->inputMock
            ->getOption('model')
            ->shouldBeCalled();
        $this->filesMock
            ->exists(Argument::containingString($this->app->path() . '/.php'))
            ->shouldBeCalled()
            ->willReturn(true);
        $this->filesMock
            ->exists(Argument::containingString($this->app->path() . '/' . config('repository.repository_path') . '/FooRepository.php'))
            ->shouldBeCalled()
            ->willReturn(false);
        $this->inputMock
            ->getArgument('name')
            ->shouldBeCalled()
            ->willReturn('Foo');
        $this->filesMock
            ->isDirectory(Argument::containingString($this->app->path()))
            ->shouldBeCalled()
            ->willReturn(true);
        $this->filesMock
            ->get(LarepositoryServiceProvider::$packageLocation . '/stubs/Repository/repository.stub')
            ->shouldBeCalled()
            ->willReturn('stub');
        $this->filesMock
            ->put(Argument::containingString($this->app->path()), 'stub')
            ->shouldBeCalled();
        $this->filesMock
            ->exists(Argument::containingString($this->app->path()))
            ->shouldBeCalled()
            ->willReturn(true);
        $this->filesMock
            ->get(Argument::containingString($this->app->path() . '/' . config('repository.provider_path', 'Providers') . '/' . RepositoryMakeCommand::$providerName . '.php'))
            ->shouldBeCalled()
            ->willReturn('repositoryBindings = [');
        $this->filesMock
            ->put(
                Argument::containingString($this->app->path() . '/' . config('repository.provider_path', 'Providers') . '/' . RepositoryMakeCommand::$providerName . '.php'),
                Argument::containingString("repositoryBindings = [\n")
            )
            ->shouldBeCalled();

        $this->subject
            ->expects($this->once())
            ->method('info')
            ->with('Repository created successfully.');
        $this->subject
            ->expects($this->once())
            ->method('call')
            ->with('make:interface', [
                'name' => 'Repositories\\FooRepository'
            ]);

        $this->subject->handle();
    }

    /**
     * @test
     */
    public function handleShouldCallMakeProviderAndAddArrayWithRepositoryBindings()
    {
        $this->inputMock
            ->hasOption('model')
            ->shouldBeCalled()
            ->willReturn(true);
        $this->inputMock
            ->getOption('model')
            ->shouldBeCalled();
        $this->filesMock
            ->exists(Argument::containingString($this->app->path() . '/.php'))
            ->shouldBeCalled()
            ->willReturn(true);
        $this->filesMock
            ->exists(Argument::containingString($this->app->path() . '/' . config('repository.repository_path') . '/FooRepository.php'))
            ->shouldBeCalled()
            ->willReturn(false);
        $this->inputMock
            ->getArgument('name')
            ->shouldBeCalled()
            ->willReturn('Foo');
        $this->filesMock
            ->isDirectory(Argument::containingString($this->app->path()))
            ->shouldBeCalled()
            ->willReturn(true);
        $this->filesMock
            ->get(LarepositoryServiceProvider::$packageLocation . '/stubs/Repository/repository.stub')
            ->shouldBeCalled()
            ->willReturn('stub');
        $this->filesMock
            ->put(Argument::containingString($this->app->path()), 'stub')
            ->shouldBeCalled();
        $this->filesMock
            ->exists(Argument::containingString($this->app->path() . '/' . config('repository.repository_path', 'Repositories') . '/BaseRepository.php'))
            ->shouldBeCalled()
            ->willReturn(true);
        $this->filesMock
            ->exists(Argument::containingString($this->app->path() . '/' . config('repository.provider_path', 'Providers') . '/' . RepositoryMakeCommand::$providerName . '.php'))
            ->shouldBeCalled()
            ->willReturn(false);
        $this->filesMock
            ->get(Argument::containingString($this->app->path() . '/' . config('repository.provider_path', 'Providers') . '/' . RepositoryMakeCommand::$providerName . '.php'))
            ->shouldBeCalled()
            ->willReturn('repositoryBindings = [');
        $this->filesMock
            ->put(
                Argument::containingString($this->app->path() . '/' . config('repository.provider_path', 'Providers') . '/' . RepositoryMakeCommand::$providerName . '.php'),
                Argument::containingString("\t\tif (!empty(\$this->repositoryBindings)) {\n\t\t\tforeach(\$this->repositoryBindings as \$abstract => \$concrete) {\n\t\t\t\t\$this->app->bind(\$abstract, \$concrete);\n\t\t\t}\n\t\t}")
            )
            ->shouldBeCalled();

        $this->subject
            ->expects($this->once())
            ->method('info')
            ->with('Repository created successfully.');
        $this->subject
            ->expects($this->exactly(2))
            ->method('call')
            ->withConsecutive(
                [
                    'make:interface',
                    [
                        'name' => 'Repositories\\FooRepository'
                    ]
                ],
                [
                    'make:provider',
                    [
                        'name' => RepositoryMakeCommand::$providerName
                    ]
                ]
            );

        $this->subject->handle();
    }
}
