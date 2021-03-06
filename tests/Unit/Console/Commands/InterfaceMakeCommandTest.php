<?php
declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Mola\Larepository\Console\Commands\InterfaceMakeCommand;
use Mola\Larepository\LarepositoryServiceProvider;
use Mola\Larepository\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\Prophecy\ProphecyInterface;
use Symfony\Component\Console\Input\InputInterface;

/**
 * InterfaceMakeCommandTest
 **/
class InterfaceMakeCommandTest extends TestCase
{
    /**
     * @var InterfaceMakeCommand|MockObject
     **/
    protected $subject;

    /**
     * @var Filesystem|ProphecyInterface
     */
    protected $filesMock;

    /**
     * @var InputInterface|ProphecyInterface
     */
    protected $inputMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesMock = $this->prophesize(Filesystem::class);

        $this->subject = $this->getMockBuilder(InterfaceMakeCommand::class)
                              ->setConstructorArgs([$this->filesMock->reveal()])
                              ->setMethods(
                                  [
                                      'info',
                                      'error'
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

        unset($this->subject, $this->filesMock);
    }

    /**
     * @test
     **/
    public function handleShouldReturnErrorMessageAndReturnIfInterfaceAlreadyExists(): void
    {
        $this->inputMock
            ->getArgument('name')
            ->shouldBeCalledTimes(2)
            ->willReturn('Foo');

        $this->filesMock
            ->exists(
                $this->app->path()
                . DIRECTORY_SEPARATOR . config('repository.contracts_path')
                . DIRECTORY_SEPARATOR . 'FooInterface.php'
            )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->subject
            ->expects($this->once())
            ->method('error')
            ->with('Interface already exists!');

        $this->subject->handle();
    }

    /**
     * @test
     **/
    public function handleShouldReturnSuccessMessageIfInterfaceWasCreated(): void
    {
        $this->inputMock
            ->getArgument('name')
            ->shouldBeCalledTimes(3)
            ->willReturn('Foo');

        $this->filesMock
            ->exists(
                $this->app->path()
                . DIRECTORY_SEPARATOR . config('repository.contracts_path')
                . DIRECTORY_SEPARATOR . 'FooInterface.php'
            )
            ->shouldBeCalled()
            ->willReturn(false);
        $this->filesMock
            ->isDirectory(
                $this->app->path()
                . DIRECTORY_SEPARATOR . config('repository.contracts_path')
            )
            ->shouldBeCalled()
            ->willReturn(true);
        $this->filesMock
            ->get(
                LarepositoryServiceProvider::$packageLocation
                . DIRECTORY_SEPARATOR . 'stubs'
                . DIRECTORY_SEPARATOR . 'Repository'
                . DIRECTORY_SEPARATOR . 'interface.stub'
            )
            ->shouldBeCalled()
            ->willReturn('interface stub');
        $this->filesMock
            ->put(
                $this->app->path()
                . DIRECTORY_SEPARATOR . config('repository.contracts_path')
                . DIRECTORY_SEPARATOR . 'FooInterface.php',
                'interface stub'
            )
            ->shouldBeCalled();

        $this->subject
            ->expects($this->once())
            ->method('info')
            ->with('Interface created successfully.');

        $this->subject->handle();
    }

    /**
     * @test
     **/
    public function handleShouldReturnSuccessMessageIfRepositoryInterfaceWasCreated(): void
    {
        $this->inputMock
            ->getArgument('name')
            ->shouldBeCalledTimes(3)
            ->willReturn('Foo', 'Foo', 'Repositories\\Foo');

        $this->filesMock
            ->exists(
                $this->app->path()
                . DIRECTORY_SEPARATOR . config('repository.contracts_path')
                . DIRECTORY_SEPARATOR . 'FooInterface.php'
            )
            ->shouldBeCalled()
            ->willReturn(false);
        $this->filesMock
            ->isDirectory($this->app->path() . DIRECTORY_SEPARATOR . config('repository.contracts_path'))
            ->shouldBeCalled()
            ->willReturn(true);
        $this->filesMock
            ->get(
                LarepositoryServiceProvider::$packageLocation
                . DIRECTORY_SEPARATOR . 'stubs'
                . DIRECTORY_SEPARATOR . 'Repository'
                . DIRECTORY_SEPARATOR . 'repository.interface.stub'
            )
            ->shouldBeCalled()
            ->willReturn('interface stub');
        $this->filesMock
            ->put(
                $this->app->path()
                . DIRECTORY_SEPARATOR . config('repository.contracts_path')
                . DIRECTORY_SEPARATOR . 'FooInterface.php',
                'interface stub'
            )
            ->shouldBeCalled();

        $this->subject
            ->expects($this->once())
            ->method('info')
            ->with('Interface created successfully.');

        $this->subject->handle();
    }
}
