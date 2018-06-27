<?php
declare(strict_types=1);

namespace Mola\Larepository\Tests\Unit\File;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Mola\Larepository\Console\Commands\RepositoryMakeCommand;
use Mola\Larepository\File\BindingsUpdater;
use Mola\Larepository\Tests\TestCase;
use Prophecy\Argument;

/**
 * BindingsUpdaterTest
 **/
class BindingsUpdaterTest extends TestCase
{
    /**
     * @var BindingsUpdater
     **/
    protected $subject;

    /**
     * @var Filesystem
     */
    protected $fileSystemMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileSystemMock = $this->prophesize(Filesystem::class);

        $this->subject = new BindingsUpdater($this->fileSystemMock->reveal());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->subject);
    }

    /**
     * @test
     */
    public function createProviderBindingsShouldReturnFalseOnEmptyInput(): void
    {
        $this->assertFalse($this->subject->createProviderBindings('', ''));
    }

    /**
     * @test
     */
    public function createProviderBindingsShouldReplaceProviderBindingsArrayWithUpdatedBindingsAndReturnTrue(): void
    {
        $expectedNeedle = 'repositoryBindings = [';
        $expectedProvider = "repositoryBindings = [\n     foo => bar";

        $this->fileSystemMock
            ->get('foobar')
            ->willReturn($expectedNeedle);
        $this->fileSystemMock
            ->exists('foobar')
            ->willReturn(true);
        $this->fileSystemMock
            ->put('foobar', $expectedProvider)
            ->willReturn(true);

        $this->assertTrue($this->subject->createProviderBindings('foobar', 'foo => bar'));
    }

    /**
     * @test
     */
    public function createProviderBindingsShouldCreateNewProviderAndReplaceEmptyBindingsArrayWithFilledArray(): void
    {
        $testProvider = "{ function register()\n&";

        $this->fileSystemMock
            ->exists('foobar')
            ->willReturn(false);

        Artisan::shouldReceive('call')
               ->with(
                   'make:provider',
                   [
                       'name' => RepositoryMakeCommand::$providerName
                   ]
               );

        $this->fileSystemMock
            ->get('foobar')
            ->willReturn($testProvider);

        $this->fileSystemMock
            ->put('foobar', Argument::containingString('$this->app->bind($abstract, $concrete);'))
            ->willReturn(true);

        $this->assertTrue($this->subject->createProviderBindings('foobar', 'foo => bar'));
    }
}
