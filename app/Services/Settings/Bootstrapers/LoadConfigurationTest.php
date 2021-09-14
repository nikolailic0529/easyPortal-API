<?php declare(strict_types = 1);

namespace App\Services\Settings\Bootstrapers;

use App\Services\Settings\Environment\EnvironmentRepository;
use App\Services\Settings\Environment\Configuration;
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
        $configuration = [
            'envs'   => ['ENV' => 'value'],
            'config' => ['SETTING' => 123],
        ];
        $config        = Mockery::mock(Configuration::class);
        $config
            ->shouldReceive('getConfiguration')
            ->once()
            ->andReturn($configuration);

        $application = Mockery::mock(Application::class);
        $application
            ->shouldReceive('make')
            ->with(Configuration::class)
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
            ->with($application, $repository, $configuration['envs'])
            ->once();
        $bootstrapper
            ->shouldReceive('loadConfig')
            ->with($application, $repository, $configuration['config'])
            ->once();

        $bootstrapper->loadConfigurationFiles($application, $repository);
    }

    /**
     * @covers ::loadConfig
     */
    public function testLoadConfig(): void {
        $config     = [
            'test' => 'value',
        ];
        $repository = Mockery::mock(Repository::class);
        $repository
            ->shouldReceive('set')
            ->with('test', 'value')
            ->andReturns();

        $bootstrapper = Mockery::mock(LoadConfiguration::class);
        $bootstrapper->shouldAllowMockingProtectedMethods();
        $bootstrapper->makePartial();

        $bootstrapper->loadConfig($this->app, $repository, $config);
    }

    /**
     * @covers ::loadEnvVars
     */
    public function testLoadEnvVars(): void {
        $app         = Mockery::mock(Application::class);
        $repository  = Mockery::mock(Repository::class);
        $environment = new EnvironmentRepository(['FOO' => 'Foo']);
        $config      = [
            'FOO' => 'Bar',
            'BAZ' => 'Hello Baz',
        ];

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
