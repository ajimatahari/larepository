<?php
declare(strict_types=1);

namespace Mola\Larepository\Tests\Unit\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Mola\Larepository\Console\Commands\RepositoryMakeCommand;
use Mola\Larepository\File\BindingsUpdater;
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
     * @var BindingsUpdater
     */
    protected $bindingsUpdateMock;

    /**
     * @var Filesystem
     */
    private $filesMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesMock = $this->prophesize(Filesystem::class);
        $this->bindingsUpdateMock = $this->prophesize(BindingsUpdater::class);

        $this->subject = $this->getMockBuilder(RepositoryMakeCommand::class)
                              ->setConstructorArgs(
                                  [
                                      $this->filesMock->reveal(),
                                      $this->bindingsUpdateMock->reveal()
                                  ]
                              )
                              ->setMethods(
                                  [
                                      'call',
                                      'error',
                                      'info'
                                  ]
                              )
                              ->getMock();

        $this->inputMock = $this->prophesize(InputInterface::class);

        $this->subject->setLaravel($this->app);

        $reflectionClass = new \ReflectionClass($this->subject);

        $reflectionProperty = $reflectionClass->getProperty('input');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->subject, $this->inputMock->reveal());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->filesMock);
    }

    /**
     * @test
     */
    public function handleShouldReturnErrorMessageIfProvidedModelDoesNotExist(): void
    {
        $this->inputMock
            ->hasArgument('model')
            ->shouldBeCalled()
            ->willReturn(true);
        $this->inputMock
            ->getArgument('model')
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
    public function handleShouldReturnErrorMessageIfModelIsNotProvided(): void
    {
        $this->inputMock
            ->hasArgument('model')
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
    public function handleShouldReturnIfParentHandleReturnsFalse(): void
    {
        $this->inputMock
            ->hasArgument('model')
            ->shouldBeCalled()
            ->willReturn(true);
        $this->inputMock
            ->getArgument('model')
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
    public function handleShouldCreateInterfaceWithProvidedName(): void
    {
        $this->inputMock
            ->hasArgument('model')
            ->shouldBeCalled()
            ->willReturn(true);
        $this->inputMock
            ->getArgument('model')
            ->shouldBeCalled();
        $this->filesMock
            ->exists(Argument::containingString($this->app->path()))
            ->shouldBeCalledTimes(2)
            ->willReturn(true, false);
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

        $this->subject
            ->expects($this->once())
            ->method('info')
            ->with('Repository created successfully.');
        $this->subject
            ->expects($this->once())
            ->method('call')
            ->with(
                'make:interface',
                [
                    'name' => 'Repositories\\FooNamespace\\FooRepository'
                ]
            );

        $this->bindingsUpdateMock
            ->createProviderBindings(
                Argument::containingString(
                    $this->app->path() . '/' . config(
                        'repository.provider_path',
                        'Providers'
                    ) . '/' . RepositoryMakeCommand::$providerName . '.php'
                ),
                Argument::containingString('FooRepository')
            )
            ->willReturn(true);

        $this->subject->handle();
    }
}
