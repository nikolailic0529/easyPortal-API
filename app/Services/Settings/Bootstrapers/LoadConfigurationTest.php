<?php declare(strict_types = 1);

namespace App\Services\Settings\Bootstrapers;

use App\Services\Settings\Config;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Settings\Bootstrapers\LoadConfiguration
 */
class LoadConfigurationTest extends TestCase {
    /**
     * @covers ::loadConfigurationFiles
     */
    public function testLoadConfigurationFiles(): void {
        $application  = Mockery::mock(Application::class);
        $application
            ->shouldReceive('configPath')
            ->once()
            ->andReturn($this->app->configPath());

        $repository   = Mockery::mock(Repository::class);
        $repository
            ->shouldReceive('set')
            ->atLeast()
            ->once()
            ->andReturns();

        $bootstrapper = Mockery::mock(LoadConfiguration::class);
        $bootstrapper->shouldAllowMockingProtectedMethods();
        $bootstrapper->makePartial();
        $bootstrapper
            ->shouldReceive('loadSettings')
            ->once();

        $bootstrapper->loadConfigurationFiles($application, $repository);
    }

    /**
     * @covers ::loadConfigurationFiles
     */
    public function testLoadSettings(): void {
        $repository   = Mockery::mock(Repository::class);
        $repository
            ->shouldReceive('set')
            ->with('test', 'value')
            ->andReturns();

        $bootstrapper = Mockery::mock(LoadConfiguration::class);
        $bootstrapper->shouldAllowMockingProtectedMethods();
        $bootstrapper->makePartial();

        $this->override(Config::class, static function (MockInterface $config): void {
            $config
                ->shouldReceive('getConfig')
                ->once()
                ->andReturn([
                    'test' => 'value',
                ]);
        });

        $bootstrapper->loadSettings($this->app, $repository);
    }
}
