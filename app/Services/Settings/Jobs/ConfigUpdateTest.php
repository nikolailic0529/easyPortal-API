<?php declare(strict_types = 1);

namespace App\Services\Settings\Jobs;


use App\Services\Settings\Settings;
use Illuminate\Contracts\Console\Kernel;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Settings\Jobs\ConfigUpdate
 */
class ConfigUpdateTest extends TestCase {
    /**
     * @covers ::handle
     */
    public function testHandleWhenConfigCached(): void {
        $kernel = Mockery::mock(Kernel::class);

        $kernel
            ->shouldReceive('call')
            ->with('config:cache')
            ->once();
        $kernel
            ->shouldReceive('call')
            ->with('queue:restart')
            ->once();

        $settings = Mockery::mock(Settings::class);
        $settings
            ->shouldReceive('isCached')
            ->once()
            ->andReturn(true);

        $this->app->make(ConfigUpdate::class)->handle($kernel, $settings);
    }
    /**
     * @covers ::handle
     */
    public function testHandleWhenConfigNotCached(): void {
        $kernel = Mockery::mock(Kernel::class);

        $kernel
            ->shouldReceive('call')
            ->with('config:cache')
            ->never();
        $kernel
            ->shouldReceive('call')
            ->with('queue:restart')
            ->once();

        $settings = Mockery::mock(Settings::class);
        $settings
            ->shouldReceive('isCached')
            ->once()
            ->andReturn(false);

        $this->app->make(ConfigUpdate::class)->handle($kernel, $settings);
    }
}
