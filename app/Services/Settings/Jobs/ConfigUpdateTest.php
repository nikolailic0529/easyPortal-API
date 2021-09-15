<?php declare(strict_types = 1);

namespace App\Services\Settings\Jobs;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\CachesConfiguration;
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
    public function testHandleWhenConfigNotCached(): void {
        $app    = Mockery::mock(Application::class);
        $kernel = Mockery::mock(Kernel::class);

        $kernel
            ->shouldReceive('call')
            ->with('config:cache')
            ->never();
        $kernel
            ->shouldReceive('call')
            ->with('queue:restart')
            ->once();

        ($this->app->make(ConfigUpdate::class))($app, $kernel);
    }
}
