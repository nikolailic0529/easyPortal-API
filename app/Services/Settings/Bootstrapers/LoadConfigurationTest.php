<?php declare(strict_types = 1);

namespace App\Services\Settings\Bootstrapers;

use App\Services\Settings\Config;
use App\Services\Settings\Testings\DotenvRepository;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Mockery;
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
        $config      = Mockery::mock(Config::class);
        $application = Mockery::mock(Application::class);
        $application
            ->shouldReceive('make')
            ->with(Config::class)
            ->andReturn($config);
        $application
            ->shouldReceive('configPath')
            ->once()
            ->andReturn($this->app->configPath());

        $repository = Mockery::mock(Repository::class);
        $repository
            ->shouldReceive('set')
            ->atLeast()
            ->once()
            ->andReturns();

        $bootstrapper = Mockery::mock(LoadConfiguration::class);
        $bootstrapper->shouldAllowMockingProtectedMethods();
        $bootstrapper->makePartial();
        $bootstrapper
            ->shouldReceive('loadEnvVars')
            ->once();
        $bootstrapper
            ->shouldReceive('loadSettings')
            ->once();

        $bootstrapper->loadConfigurationFiles($application, $repository);
    }

    /**
     * @covers ::loadSettings
     */
    public function testLoadSettings(): void {
        $config = Mockery::mock(Config::class);
        $config
            ->shouldReceive('getConfig')
            ->once()
            ->andReturn([
                'test' => 'value',
            ]);

        $repository = Mockery::mock(Repository::class);
        $repository
            ->shouldReceive('set')
            ->with('test', 'value')
            ->andReturns();

        $bootstrapper = Mockery::mock(LoadConfiguration::class);
        $bootstrapper->shouldAllowMockingProtectedMethods();
        $bootstrapper->makePartial();

        $bootstrapper->loadSettings($this->app, $repository, $config);
    }

    /**
     * @covers ::loadEnvVars
     */
    public function testLoadEnvVars(): void {
        $app         = Mockery::mock(Application::class);
        $repository  = Mockery::mock(Repository::class);
        $environment = new DotenvRepository(['FOO' => 'Foo']);
        $config      = Mockery::mock(Config::class);
        $config
            ->shouldReceive('getEnvVars')
            ->once()
            ->andReturn([
                'FOO' => 'Bar',
                'BAZ' => 'Hello Baz',
            ]);

        $bootstrapper = Mockery::mock(LoadConfiguration::class);
        $bootstrapper->shouldAllowMockingProtectedMethods();
        $bootstrapper->makePartial();
        $bootstrapper
            ->shouldReceive('getEnvRepository')
            ->once()
            ->andReturn($environment);

        $bootstrapper->loadEnvVars($app, $repository, $config);

        $this->assertEquals([
            'FOO' => 'Foo',
            'BAZ' => 'Hello Baz',
        ], $environment->getVars());
    }
}
