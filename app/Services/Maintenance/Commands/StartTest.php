<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Commands;

use App\Services\Maintenance\Maintenance;
use DateTimeInterface;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Date;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Maintenance\Commands\Start
 */
class StartTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testCommand(): void {
        self::assertCommandDescription('ep:maintenance-start');
    }

    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertArrayHasKey('ep:maintenance-start', $this->app->make(Kernel::class)->all());
    }

    public function testHandleSuccessNoWait(): void {
        $this->override(Maintenance::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('start')
                ->withArgs(static function (DateTimeInterface $end, ?string $message, bool $force): bool {
                    return Date::make($end)->diffInHours(Date::now()->subMinute()) === 2
                        && $message === 'message'
                        && $force === false;
                })
                ->once()
                ->andReturn(true);
        });

        $this
            ->artisan('ep:maintenance-start', [
                '--duration' => '2 hours',
                '--message'  => 'message',
                '--no-wait'  => true,
            ])
            ->assertSuccessful()
            ->expectsOutput('Done.');
    }

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
            ->artisan('ep:maintenance-start', [
                '--duration' => '2 hours',
                '--message'  => 'message',
            ])
            ->assertSuccessful()
            ->expectsOutput('Done.');
    }

    public function testHandleSuccessForce(): void {
        $this->override(Maintenance::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('start')
                ->withArgs(static function (DateTimeInterface $end, ?string $message, bool $force): bool {
                    return Date::make($end)->diffInHours(Date::now()->subMinute()) === 2
                        && $message === 'message'
                        && $force === true;
                })
                ->once()
                ->andReturn(true);
        });

        $this
            ->artisan('ep:maintenance-start', [
                '--duration' => '2 hours',
                '--message'  => 'message',
                '--force'    => true,
            ])
            ->assertSuccessful()
            ->expectsOutput('Done.');
    }

    public function testHandleFailed(): void {
        $this->override(Maintenance::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('start')
                ->once()
                ->andReturn(false);
        });

        $this
            ->artisan('ep:maintenance-start')
            ->assertFailed()
            ->expectsOutput('Failed.');
    }
}
