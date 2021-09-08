<?php declare(strict_types = 1);

namespace App\Services\Settings;

use App\Services\Settings\Exceptions\FailedToLoadEnv;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Settings\EnvLoader
 */
class EnvLoaderTest extends TestCase {
    /**
     * @covers ::load
     */
    public function testLoadCached(): void {
        $app = Mockery::mock(Application::class);
        $app
            ->shouldReceive('configurationIsCached')
            ->once()
            ->andReturn(true);
        $app
            ->shouldReceive('environment')
            ->twice()
            ->andReturn('test');
        $app
            ->shouldReceive('environmentFile')
            ->once()
            ->andReturn('.env');

        $loader = Mockery::mock(EnvLoader::class);
        $loader->makePartial();
        $loader->shouldAllowMockingProtectedMethods();
        $loader
            ->shouldReceive('setEnvironmentFilePath')
            ->once()
            ->andReturn(false);
        $loader
            ->shouldReceive('createDotenv')
            ->once()
            ->andReturn(new class {
                public function safeLoad(): mixed {
                    return [];
                }
            });

        $loader->load($app);

        $this->assertTrue(true);
    }

    /**
     * @covers ::load
     */
    public function testLoadFailed(): void {
        $app = Mockery::mock(Application::class);
        $app
            ->shouldReceive('configurationIsCached')
            ->once()
            ->andReturn(true);
        $app
            ->shouldReceive('environment')
            ->twice()
            ->andReturn('test');
        $app
            ->shouldReceive('environmentFile')
            ->once()
            ->andReturn('.env');
        $app
            ->shouldReceive('environmentPath')
            ->once()
            ->andReturn('path/to/env');
        $app
            ->shouldReceive('environmentFile')
            ->once()
            ->andReturn('.env.file');

        $loader = Mockery::mock(EnvLoader::class);
        $loader->makePartial();
        $loader->shouldAllowMockingProtectedMethods();
        $loader
            ->shouldReceive('setEnvironmentFilePath')
            ->once()
            ->andReturn(false);
        $loader
            ->shouldReceive('createDotenv')
            ->once()
            ->andReturnUsing(static function (): void {
                throw new Exception();
            });

        $this->expectException(FailedToLoadEnv::class);

        $loader->load($app);
    }

    /**
     * @covers ::load
     */
    public function testLoadNotCached(): void {
        $app = Mockery::mock(Application::class);
        $app
            ->shouldReceive('configurationIsCached')
            ->once()
            ->andReturn(false);

        $loader = Mockery::mock(EnvLoader::class);
        $loader->makePartial();
        $loader->shouldAllowMockingProtectedMethods();
        $loader
            ->shouldReceive('checkForSpecificEnvironmentFile')
            ->never();

        $loader->load($app);

        $this->assertTrue(true);
    }
}
