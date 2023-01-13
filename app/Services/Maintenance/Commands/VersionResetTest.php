<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Commands;

use App\Services\Maintenance\ApplicationInfo;
use App\Services\Maintenance\Events\VersionUpdated;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Maintenance\Commands\VersionReset
 */
class VersionResetTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testCommand(): void {
        self::assertCommandDescription('ep:maintenance-version-reset');
    }

    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertArrayHasKey('ep:maintenance-version-reset', $this->app->make(Kernel::class)->all());
    }

    public function testInvokeSuccess(): void {
        $path    = 'path/to/version.php';
        $current = $this->faker->semver();

        $this->override(Filesystem::class, static function (MockInterface $mock) use ($path): void {
            $mock
                ->shouldReceive('exists')
                ->with($path)
                ->once()
                ->andReturn(true);
            $mock
                ->shouldReceive('delete')
                ->with($path)
                ->once()
                ->andReturn(true);
        });

        $this->override(ApplicationInfo::class, static function (MockInterface $mock) use ($path, $current): void {
            $mock
                ->shouldReceive('getCachedVersionPath')
                ->once()
                ->andReturn($path);
            $mock
                ->shouldReceive('getVersion')
                ->once()
                ->andReturn($current);
            $mock
                ->shouldReceive('getVersion')
                ->once()
                ->andReturn(ApplicationInfo::DEFAULT_VERSION);
        });

        Event::fake(VersionUpdated::class);

        $this
            ->artisan('ep:maintenance-version-reset')
            ->expectsOutput('Done.')
            ->assertSuccessful();

        Event::assertDispatched(VersionUpdated::class, 1);
        Event::assertDispatched(
            VersionUpdated::class,
            static function (VersionUpdated $event) use ($current): bool {
                return $event->getVersion() === ApplicationInfo::DEFAULT_VERSION
                    && $event->getPrevious() === $current;
            },
        );
    }

    public function testInvokeFailed(): void {
        $path = 'path/to/version.php';

        $this->override(Filesystem::class, static function (MockInterface $mock) use ($path): void {
            $mock
                ->shouldReceive('exists')
                ->with($path)
                ->once()
                ->andReturn(true);
            $mock
                ->shouldReceive('delete')
                ->with($path)
                ->once()
                ->andReturn(false);
        });

        $this->override(ApplicationInfo::class, static function (MockInterface $mock) use ($path): void {
            $mock
                ->shouldReceive('getCachedVersionPath')
                ->once()
                ->andReturn($path);
            $mock
                ->shouldReceive('getVersion')
                ->once()
                ->andReturn(ApplicationInfo::DEFAULT_VERSION);
        });

        Event::fake(VersionUpdated::class);

        $this
            ->artisan('ep:maintenance-version-reset')
            ->expectsOutput('Failed.')
            ->assertFailed();

        Event::assertNotDispatched(VersionUpdated::class);
    }
}
