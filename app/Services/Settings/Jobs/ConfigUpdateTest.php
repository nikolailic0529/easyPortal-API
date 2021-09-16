<?php declare(strict_types = 1);

namespace App\Services\Settings\Jobs;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Settings\Jobs\ConfigUpdate
 */
class ConfigUpdateTest extends TestCase {
    /**
     * @covers ::__invoke
     */
    public function testHandleWhenConfigCached(): void {
        $app = Mockery::mock(Application::class, CachesConfiguration::class);
        $app
            ->shouldReceive('configurationIsCached')
            ->once()
            ->andReturn(true);

        $kernel = Mockery::mock(Kernel::class);
        $kernel
            ->shouldReceive('call')
            ->with('config:cache')
            ->once();
        $kernel
            ->shouldReceive('call')
            ->with('queue:restart')
            ->once();

        ($this->app->make(ConfigUpdate::class))($app, $kernel);
    }
    /**
     * @covers ::__invoke
     */
    public function testHandleWhenRoutesCached(): void {
        $app = Mockery::mock(Application::class, CachesRoutes::class);
        $app
            ->shouldReceive('routesAreCached')
            ->once()
            ->andReturn(true);

        $kernel = Mockery::mock(Kernel::class);
        $kernel
            ->shouldReceive('call')
            ->with('route:cache')
            ->once();
        $kernel
            ->shouldReceive('call')
            ->with('queue:restart')
            ->once();

        ($this->app->make(ConfigUpdate::class))($app, $kernel);
    }

    /**
     * @covers ::__invoke
     */
    public function testHandleWhenNotCached(): void {
        $app    = Mockery::mock(Application::class);
        $kernel = Mockery::mock(Kernel::class);

        $kernel
            ->shouldReceive('call')
            ->with('queue:restart')
            ->once();

        ($this->app->make(ConfigUpdate::class))($app, $kernel);
    }
}
