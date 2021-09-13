<?php declare(strict_types = 1);

namespace App\Services\Settings\Bootstrapers;

use App\Services\Settings\Exceptions\FailedToLoadSettings;
use Dotenv\Dotenv;
use Dotenv\Repository\RepositoryInterface;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use LastDragon_ru\LaraASP\Testing\Utils\WithTempFile;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Settings\Bootstrapers\LoadEnvironmentVariables
 */
class LoadEnvironmentVariablesTest extends TestCase {
    use WithTempFile;

    /**
     * @covers ::bootstrap
     */
    public function testBootstrap(): void {
        $dotenv = Mockery::mock(Dotenv::class);
        $dotenv
            ->shouldReceive('safeLoad')
            ->once()
            ->andReturn([]);

        $bootstrapper = Mockery::mock(LoadEnvironmentVariables::class);
        $bootstrapper->shouldAllowMockingProtectedMethods();
        $bootstrapper->makePartial();
        $bootstrapper
            ->shouldReceive('checkForSpecificEnvironmentFile')
            ->once()
            ->andReturns();
        $bootstrapper
            ->shouldReceive('createDotenv')
            ->once()
            ->andReturn($dotenv);
        $bootstrapper
            ->shouldReceive('loadSettings')
            ->once();

        $application = Mockery::mock(Application::class);
        $application
            ->shouldReceive('configurationIsCached')
            ->twice()
            ->andReturn(false);

        $bootstrapper->bootstrap($application);
    }

    /**
     * @covers ::bootstrap
     */
    public function testBootstrapCached(): void {
        $bootstrapper = Mockery::mock(LoadEnvironmentVariables::class);
        $bootstrapper->shouldAllowMockingProtectedMethods();
        $bootstrapper->makePartial();
        $bootstrapper
            ->shouldReceive('loadSettings')
            ->never();

        $application = Mockery::mock(Application::class);
        $application
            ->shouldReceive('configurationIsCached')
            ->twice()
            ->andReturn(true);

        $bootstrapper->bootstrap($application);
    }

    /**
     * @covers ::loadSettings
     */
    public function testLoadSettings(): void {
        $path         = $this->getTempFile("FOO=Bar\nBAZ=\"Hello \${FOO}\"")->getPathname();
        $repository   = new class(['FOO' => 'Foo']) implements RepositoryInterface {
            /**
             * @param array<string,mixed> $vars
             */
            public function __construct(
                protected array $vars,
            ) {
                // empty
            }

            /**
             * @return array<string,mixed>
             */
            public function getVars(): array {
                return $this->vars;
            }

            public function has(string $name): bool {
                return isset($this->vars[$name]);
            }

            public function get(string $name): mixed {
                return $this->vars[$name] ?? null;
            }

            public function set(string $name, string $value): bool {
                $this->vars[$name] = $value;

                return true;
            }

            public function clear(string $name): bool {
                $this->vars = [];

                return true;
            }
        };
        $bootstrapper = Mockery::mock(LoadEnvironmentVariables::class);
        $bootstrapper->shouldAllowMockingProtectedMethods();
        $bootstrapper->makePartial();
        $bootstrapper
            ->shouldReceive('getSettingsPath')
            ->once()
            ->andReturn($path);
        $bootstrapper
            ->shouldReceive('getEnvRepository')
            ->once()
            ->andReturn($repository);

        $bootstrapper->loadSettings($this->app);

        $this->assertEquals([
            'FOO' => 'Foo',
            'BAZ' => 'Hello Bar',
        ], $repository->getVars());
    }

    /**
     * @covers ::loadSettings
     */
    public function testLoadSettingNoFile(): void {
        $path         = 'not a file';
        $repository   = Mockery::mock(RepositoryInterface::class);
        $bootstrapper = Mockery::mock(LoadEnvironmentVariables::class);
        $bootstrapper->shouldAllowMockingProtectedMethods();
        $bootstrapper->makePartial();
        $bootstrapper
            ->shouldReceive('getSettingsPath')
            ->once()
            ->andReturn($path);
        $bootstrapper
            ->shouldReceive('getEnvRepository')
            ->once()
            ->andReturn($repository);

        $bootstrapper->loadSettings($this->app);
    }

    /**
     * @covers ::loadSettings
     */
    public function testLoadSettingsError(): void {
        $path         = 'not a file';
        $exception    = new Exception();
        $bootstrapper = Mockery::mock(LoadEnvironmentVariables::class);
        $bootstrapper->shouldAllowMockingProtectedMethods();
        $bootstrapper->makePartial();
        $bootstrapper
            ->shouldReceive('getSettingsPath')
            ->once()
            ->andReturn($path);
        $bootstrapper
            ->shouldReceive('getEnvRepository')
            ->once()
            ->andThrow($exception);

        $this->expectExceptionObject(new FailedToLoadSettings($path, $exception));

        $bootstrapper->loadSettings($this->app);
    }
}
