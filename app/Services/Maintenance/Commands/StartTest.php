<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Commands;

use App\Services\Maintenance\Maintenance;
use DateTimeInterface;
use Illuminate\Support\Facades\Date;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Maintenance\Commands\Start
 */
class StartTest extends TestCase {
    /**
     * @covers ::handle
     */
    public function testHandleSuccessNoWait(): void {
        $this->override(Maintenance::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('start')
                ->withArgs(static function (DateTimeInterface $end, ?string $message): bool {
                    return Date::make($end)->diffInHours(Date::now()->subMinute()) === 2
                        && $message === 'message';
                })
                ->once()
                ->andReturn(true);
        });

        $this
            ->artisan('ep:data-maintenance-start', [
                '--duration' => '2 hours',
                '--message'  => 'message',
                '--no-wait'  => true,
            ])
            ->assertSuccessful()
            ->expectsOutput('Done.');
    }

    /**
     * @covers ::handle
     */
    public function testHandleSuccessWait(): void {
        $this->override(Maintenance::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('start')
                ->withArgs(static function (DateTimeInterface $end, ?string $message): bool {
                    return Date::make($end)->diffInHours(Date::now()->subMinute()) === 2
                        && $message === 'message';
                })
                ->once()
                ->andReturn(true);
            $mock
                ->shouldReceive('isEnabled')
                ->twice()
                ->andReturn(false, true);
        });

        $this
            ->artisan('ep:data-maintenance-start', [
                '--duration' => '2 hours',
                '--message'  => 'message',
            ])
            ->assertSuccessful()
            ->expectsOutput('Done.');
    }

    /**
     * @covers ::handle
     */
    public function testHandleFailed(): void {
        $this->override(Maintenance::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('start')
                ->once()
                ->andReturn(false);
        });

        $this
            ->artisan('ep:data-maintenance-start')
            ->assertFailed()
            ->expectsOutput('Failed.');
    }
}
