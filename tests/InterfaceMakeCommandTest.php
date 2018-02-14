<?php

namespace Mola\Larepository\Tests\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Mola\Larepository\LarepositoryServiceProvider;
use Mola\Larepository\Tests\TestCase;
use Prophecy\Argument;

/**
 * InterfaceMakeCommandTest
 **/
class InterfaceMakeCommandTest extends TestCase
{
    /**
     * @var Filesystem
     */
    private $filesMock;

    public function setUp()
    {
        parent::setUp();

        $this->filesMock = $this->prophesize(Filesystem::class);

        $this->app->instance(Filesystem::class, $this->filesMock->reveal());
    }

    public function tearDown()
    {
        parent::tearDown();

        unset($this->filesMock);
    }

    /**
     * @test
     **/
    public function itShouldReturnIfParentHandleMethodReturnsFalse()
    {
        $this->filesMock
            ->exists(
                $this->app->path(config('repository.contracts_path')
                    . '/FooInterface.php'
                )
            )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->artisan('make:interface', [
            'name' => 'Foo',
            '--crud' => true
        ]);

        $this->assertEquals(
            'Interface already exists!' . PHP_EOL,
            Artisan::output()
        );
    }

    /**
     * @test
     */
    public function itShouldUseCrudStubForFileCreationOnProvidedCrudFlag()
    {
        $this->filesMock
            ->exists($this->app->path(config('repository.contracts_path') . '/FooInterface.php'))
            ->shouldBeCalled()
            ->willReturn(false);
        $this->filesMock
            ->isDirectory($this->app->path(config('repository.contracts_path')))
            ->shouldBeCalled()
            ->willReturn(true);
        $this->filesMock
            ->get(Argument::containingString('repository.interface.stub'))
            ->shouldBeCalled();
        $this->filesMock
            ->put($this->app->path(config('repository.contracts_path') . '/FooInterface.php'), '')
            ->shouldBeCalled();

        $this->artisan('make:interface', [
            'name' => 'Foo',
            '--crud' => ''
        ]);

        $this->assertEquals(
            'Interface created successfully.' . PHP_EOL,
            Artisan::output()
        );
    }

    /**
     * @test
     */
    public function itShouldUseDefaultStubForFileCreationWithoutProvidedCrudFlag()
    {
        $this->filesMock
            ->exists($this->app->path(config('repository.contracts_path') . '/FooInterface.php'))
            ->shouldBeCalled()
            ->willReturn(false);
        $this->filesMock
            ->isDirectory($this->app->path(config('repository.contracts_path')))
            ->shouldBeCalled()
            ->willReturn(true);
        $this->filesMock
            ->get(Argument::containingString('repository-crud.interface.stub'))
            ->shouldBeCalled();
        $this->filesMock
            ->put($this->app->path(config('repository.contracts_path') . '/FooInterface.php'), '')
            ->shouldBeCalled();

        $this->artisan('make:interface', [
            'name' => 'Foo',
            '--crud' => true
        ]);

        $this->assertEquals(
            'Interface created successfully.' . PHP_EOL,
            Artisan::output()
        );
    }
}
