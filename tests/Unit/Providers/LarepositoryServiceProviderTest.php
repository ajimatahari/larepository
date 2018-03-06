<?php

namespace Mola\Larepository\Tests\Unit\Providers;

use Illuminate\Filesystem\Filesystem;
use Mola\Larepository\Console\Commands\InterfaceMakeCommand;
use Mola\Larepository\Console\Commands\RepositoryMakeCommand;
use Mola\Larepository\LarepositoryServiceProvider;
use Mola\Larepository\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * LarepositoryServiceProviderTest
 **/
class LarepositoryServiceProviderTest extends TestCase
{
    /**
     * @var \Illuminate\Foundation\Application|MockObject
     */
    private $appMock;

    /**
     * @var LarepositoryServiceProvider|MockObject
     **/
    protected $subject;

    public function setUp()
    {
        parent::setUp();

        $this->appMock = \Mockery::mock(\Illuminate\Foundation\Application::class)->makePartial();

        $this->subject = $this->getMockBuilder(LarepositoryServiceProvider::class)
            ->setConstructorArgs([$this->appMock])
            ->setMethods(['runningInConsole', 'commands'])
            ->getMock();
    }

    public function tearDown()
    {
        parent::tearDown();

        unset($this->subject);
    }

    /**
    * @test
    */
    public function itShouldHavePackageLocationPerDefault()
    {
        $this->assertEquals(getcwd(), $this->subject::$packageLocation);
    }

    /**
     * @test
     **/
    public function bootShouldPublishConfigurationFilesOnRequest()
    {
        $this->subject::$publishes = [];

        $this->subject->boot();

        $this->assertArrayHasKey(get_class($this->subject), $this->subject::$publishes);
        $this->assertEquals(
            [
                get_class($this->subject) => [
                    $this->subject::$packageLocation . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'repository.php' => config_path('repository.php')
                ]
            ],
            $this->subject::$publishes
        );
    }

    /**
     * @test
     */
    public function bootShouldRegisterCommandsWhenRunInConsole()
    {
        $this->appMock
            ->shouldReceive('runningInConsole')
            ->once()
            ->andReturn(true);

        $this->subject
            ->expects($this->once())
            ->method('commands')
            ->with([
                InterfaceMakeCommand::class,
                RepositoryMakeCommand::class
            ]);

        $this->subject->boot();
    }

    /**
     * @test
     */
    public function bootShouldNotRegisterCommandsWhenNotRunInConsole()
    {
        $this->appMock
            ->shouldReceive('runningInConsole')
            ->once()
            ->andReturn(false);

        $this->subject
            ->expects($this->never())
            ->method('commands')
            ->with([
                InterfaceMakeCommand::class,
                RepositoryMakeCommand::class
            ]);

        $this->subject->boot();
    }

    /**
     * @test
     */
    public function registerShouldRegisterRepositoryServiceProviderIfExists()
    {
        $filesMock = $this->prophesize(Filesystem::class);
        $filesMock
            ->exists(app_path('Providers' . DIRECTORY_SEPARATOR . RepositoryMakeCommand::$providerName . '.php'))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->appMock
            ->shouldReceive('make')
            ->once()
            ->with(Filesystem::class)
            ->andReturn($filesMock->reveal());
        $this->appMock
            ->shouldReceive('register')
            ->once()
            ->with($this->app->getNamespace() . 'Providers\\' . RepositoryMakeCommand::$providerName);

        $this->subject->register();
    }

    /**
     * @test
     */
    public function registerShouldNotRegisterRepositoryServiceProviderIfItDoesNotExist()
    {
        $filesMock = $this->prophesize(Filesystem::class);
        $filesMock
            ->exists(app_path('Providers' . DIRECTORY_SEPARATOR . RepositoryMakeCommand::$providerName . '.php'))
            ->shouldBeCalled()
            ->willReturn(false);

        $this->appMock
            ->shouldReceive('make')
            ->with(Filesystem::class)
            ->andReturn($filesMock->reveal());
        $this->appMock
            ->shouldNotReceive('register')
            ->with($this->app->getNamespace() . 'Providers\\' . RepositoryMakeCommand::$providerName);

        $this->subject->register();
    }
}
