<?php

namespace Mola\Larepository\Tests\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Mola\Larepository\Console\Commands\RepositoryCommand;
use Mola\Larepository\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\Argument;
use Prophecy\Prophecy\ProphecyInterface;
use Symfony\Component\Console\Input\InputInterface;

/**
 * RepositoryCommandTest
 **/
class RepositoryCommandTest extends TestCase
{
    /**
     * @var RepositoryCommand|MockObject
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

        $this->subject = $this->getMockBuilder(RepositoryCommand::class)
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
     **/
    public function itShouldReturnErrorMessageIfProvidedModelIsNotFound()
    {
        $this->filesMock
            ->exists($this->app->path(config('repository.model_path') . '/Foo.php'))
            ->shouldBeCalled()
            ->willReturn(false);

        $this->subject
            ->expects($this->once())
            ->method('error')
            ->with('Model does not exist! Please provide correct namespace.');

        $this->inputMock
            ->hasOption('model')
            ->shouldBeCalled()
            ->willReturn(true);
        $this->inputMock
            ->getOption('model')
            ->shouldBeCalled()
            ->willReturn('Foo');

        $this->subject->handle();
    }

    /**
     * @test
     */
    public function itShouldReturnIfParentHandleMethodReturnsFalse()
    {
        $this->inputMock
            ->hasOption('model')
            ->shouldBeCalled()
            ->willReturn(false);
        $this->inputMock
            ->getArgument('name')
            ->shouldBeCalled()
            ->willReturn('Foo');

        $this->filesMock
            ->exists(Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->subject
            ->expects($this->once())
            ->method('error')
            ->with('Repository already exists!');

        $this->subject->handle();
    }

    /**
     * @test
     */
    public function itShouldCallMakeInterfaceCommandWithRepositoryParameters()
    {
        $this->inputMock
            ->hasOption('model')
            ->shouldBeCalled()
            ->willReturn(false);
        $this->inputMock
            ->getOption('model')
            ->shouldBeCalled()
            ->willReturn(false);
        $this->inputMock
            ->getArgument('name')
            ->shouldBeCalled()
            ->willReturn('Foo');

        $this->filesMock
            ->exists(
                $this->app->path(
                    config('repository.repository_path') . '/FooRepository.php'
                )
            )
            ->shouldBeCalled()
            ->willReturn(false);
        $this->filesMock
            ->isDirectory($this->app->path(config('repository.repository_path')))
            ->shouldBeCalled()
            ->willReturn(true);
        $this->filesMock
            ->put(
                $this->app->path('Providers/RepositoryServiceProvider.php'),
                Argument::containingString("repositoryBindings = [\n")
            )
            ->shouldBeCalled();
        $this->filesMock
            ->exists($this->app->path('Providers/RepositoryServiceProvider.php'))
            ->shouldBeCalled()
            ->willReturn(true);
        $this->filesMock
            ->get(Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn('repositoryBindings = [');
        $this->filesMock
            ->put(Argument::type('string'), Argument::type('string'))
            ->shouldBeCalled();

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
                    'name' => 'Repositories\FooRepository',
                    '--crud' => false
                ]
            );

        $this->subject->handle();
    }

    /**
     * @test
     */
    public function itShouldCallMakeProviderCommandIfNoProviderExists()
    {
        $this->inputMock
            ->hasOption('model')
            ->shouldBeCalled()
            ->willReturn(false, true, false);
        $this->inputMock
            ->getOption('model')
            ->shouldBeCalled()
            ->willReturn(false);
        $this->inputMock
            ->getArgument('name')
            ->shouldBeCalled()
            ->willReturn('Foo');

        $this->filesMock
            ->exists(
                $this->app->path(
                    config('repository.repository_path') . '/FooRepository.php'
                )
            )
            ->shouldBeCalled()
            ->willReturn(false);
        $this->filesMock
            ->isDirectory($this->app->path(config('repository.repository_path')))
            ->shouldBeCalled()
            ->willReturn(true);
        $this->filesMock
            ->put(
                $this->app->path('Providers/RepositoryServiceProvider.php'),
                Argument::containingString('!empty($this->repositoryBindings)')
            )
            ->shouldBeCalled();
        $this->filesMock
            ->exists($this->app->path('Providers/RepositoryServiceProvider.php'))
            ->shouldBeCalled()
            ->willReturn(false);
        $this->filesMock
            ->get(Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn('{');
        $this->filesMock
            ->put(
                $this->app->path(config('repository.repository_path') . '/FooRepository.php'),
                '{'
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
                        'name' => 'Repositories\FooRepository',
                        '--crud' => false
                    ]
                ],
                [
                    'make:provider',
                    [
                        'name' => 'RepositoryServiceProvider'
                    ]
                ]
            );

        $this->subject->handle();
    }
}
