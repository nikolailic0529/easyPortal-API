<?php declare(strict_types = 1);

namespace App\Services\Settings\Bootstrapers;

use App\Services\Settings\Environment\Configuration;
use App\Services\Settings\Environment\EnvironmentRepository;
use Closure;
use Config\Constants;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Mockery;
use ReflectionClass;
use Tests\TestCase;

use function pathinfo;

use const PATHINFO_FILENAME;

/**
 * @internal
 * @covers \App\Services\Settings\Bootstrapers\LoadConfiguration
 */
class LoadConfigurationTest extends TestCase {
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
        $application
            ->shouldReceive('booted')
            ->once()
            ->andReturnUsing(static function (Closure $closure): void {
                $closure();
            });

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
            ->shouldReceive('overwriteEnvVars')
            ->with($application, $repository, $configuration['envs'])
            ->once();
        $bootstrapper
            ->shouldReceive('cleanupEnvVars')
            ->with($application, $repository, $configuration['envs'])
            ->once();
        $bootstrapper
            ->shouldReceive('overwriteConfig')
            ->with($application, $repository, $configuration['config'])
            ->once();

        $bootstrapper->loadConfigurationFiles($application, $repository);
    }

    public function testOverwriteConfig(): void {
        $spy        = Mockery::spy(static function (): void {
            // empty
        });
        $config     = [
            'test' => 'value',
        ];
        $repository = Mockery::mock(Repository::class);
        $repository
            ->shouldReceive('set')
            ->with('test', 'value')
            ->andReturnUsing(Closure::fromCallable($spy));

        $bootstrapper = Mockery::mock(LoadConfiguration::class);
        $bootstrapper->shouldAllowMockingProtectedMethods();
        $bootstrapper->makePartial();

        $bootstrapper->overwriteConfig($this->app, $repository, $config);

        $spy->shouldHaveBeenCalled();
    }

    public function testOverwriteEnvVars(): void {
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

        $bootstrapper->overwriteEnvVars($app, $repository, $config);

        self::assertEquals([
            'FOO' => 'Foo',
            'BAZ' => 'Hello Baz',
        ], $environment->getVars());
    }

    public function testCleanupEnvVars(): void {
        $app         = Mockery::mock(Application::class);
        $repository  = Mockery::mock(Repository::class);
        $environment = new EnvironmentRepository(['FOO' => 'Foo', 'BAZ' => 'Baz']);
        $config      = [
            'BAZ' => 'Hello Baz',
        ];

        $bootstrapper = Mockery::mock(LoadConfiguration::class);
        $bootstrapper->shouldAllowMockingProtectedMethods();
        $bootstrapper->makePartial();
        $bootstrapper
            ->shouldReceive('getEnvRepository')
            ->once()
            ->andReturn($environment);

        $bootstrapper->cleanupEnvVars($app, $repository, $config);

        self::assertEquals([
            'FOO' => 'Foo',
        ], $environment->getVars());
    }

    public function testGetConfigurationFiles(): void {
        $unexpected = pathinfo((string) (new ReflectionClass(Constants::class))->getFileName(), PATHINFO_FILENAME);
        $files      = (new class() extends LoadConfiguration {
            /**
             * @inheritDoc
             */
            public function getConfigurationFiles(Application $app): array {
                return parent::getConfigurationFiles($app);
            }
        })->getConfigurationFiles($this->app);

        self::assertArrayNotHasKey($unexpected, $files);
    }
}
