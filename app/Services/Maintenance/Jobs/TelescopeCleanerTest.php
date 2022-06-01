<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Jobs;

use Illuminate\Contracts\Console\Kernel;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Maintenance\Jobs\TelescopeCleaner
 */
class TelescopeCleanerTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertCronableRegistered(TelescopeCleaner::class);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvoke(): void {
        $job = $this->app->make(TelescopeCleaner::class);

        $this->override(Kernel::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('call')
                ->with('telescope:prune', [
                    '--hours' => 123,
                ])
                ->once();
        });

        $this->setQueueableConfig(TelescopeCleaner::class, [
            'settings' => [
                'expire' => '123 hours',
            ],
        ]);

        $this->app->call($job);
    }
}