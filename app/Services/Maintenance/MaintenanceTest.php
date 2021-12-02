<?php declare(strict_types = 1);

namespace App\Services\Maintenance;

use App\Services\Maintenance\Jobs\DisableCronJob;
use App\Services\Maintenance\Jobs\EnableCronJob;
use App\Services\Settings\Settings as SettingsService;
use DateInterval;
use DateTime;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Queue;
use LastDragon_ru\LaraASP\Queue\Configs\CronableConfig;
use LastDragon_ru\LaraASP\Queue\Contracts\Cronable;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

use function ltrim;

/**
 * @internal
 * @coversDefaultClass \App\Services\Maintenance\Maintenance
 */
class MaintenanceTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::getSettings
     */
    public function testGetSettings(): void {
        $maintenance = $this->app->make(Maintenance::class);
        $settings    = new Settings([
            'start'   => Date::now()->startOfDay(),
            'message' => $this->faker->sentence,
        ]);
        $storage     = $this->app->make(Storage::class);

        $this->assertTrue($storage->save($settings->toArray()));
        $this->assertEquals($settings, $maintenance->getSettings());
    }

    /**
     * @covers ::getSettings
     */
    public function testGetSettingsDownForMaintenance(): void {
        $this->override(Application::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('isDownForMaintenance')
                ->once()
                ->andReturn(true);
        });

        $maintenance = $this->app->make(Maintenance::class);
        $expected    = Settings::make([
            'enabled' => true,
        ]);

        $this->assertEquals($expected, $maintenance->getSettings());
    }

    /**
     * @covers ::isEnabled
     */
    public function testIsEnabled(): void {
        $maintenance = $this->app->make(Maintenance::class);
        $settings    = new Settings([
            'enabled' => $this->faker->boolean,
        ]);
        $storage     = $this->app->make(Storage::class);

        $this->assertTrue($storage->save($settings->toArray()));
        $this->assertEquals($settings->enabled, $maintenance->isEnabled());
    }

    /**
     * @covers ::isEnabled
     */
    public function testIsEnabledDownForMaintenance(): void {
        $this->override(Application::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('isDownForMaintenance')
                ->once()
                ->andReturn(true);
        });

        $maintenance = $this->app->make(Maintenance::class);

        $this->assertTrue($maintenance->isEnabled());
    }

    /**
     * @covers ::enable
     */
    public function testEnable(): void {
        $maintenance = $this->app->make(Maintenance::class);
        $settings    = new Settings([
            'enabled' => false,
        ]);
        $storage     = $this->app->make(Storage::class);

        $this->assertTrue($storage->save($settings->toArray()));
        $this->assertFalse($maintenance->isEnabled());
        $this->assertTrue($maintenance->enable());
        $this->assertTrue($maintenance->isEnabled());
    }

    /**
     * @covers ::disable
     */
    public function testDisable(): void {
        $maintenance = $this->app->make(Maintenance::class);
        $storage     = $this->app->make(Storage::class);
        $data        = ['enabled' => true];

        $this->assertTrue($storage->save($data));
        $this->assertEquals($data, $storage->load());
        $this->assertTrue($maintenance->disable());
        $this->assertEquals([], $storage->load());
    }

    /**
     * @covers ::schedule
     */
    public function testSchedule(): void {
        $message = $this->faker->sentence;
        $start   = Date::make('2021-11-30T10:15:00+00:00');
        $end     = Date::make('2022-01-14T11:15:22+00:00');

        $this->override(SettingsService::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('setEditableSettings')
                ->with([
                    'EP_MAINTENANCE_ENABLE_CRON'  => '15 10 30 11 2',
                    'EP_MAINTENANCE_DISABLE_CRON' => '15 11 14 1 5',
                ])
                ->once()
                ->andReturn([]);
        });

        $maintenance = $this->app->make(Maintenance::class);
        $storage     = $this->app->make(Storage::class);

        $this->assertTrue($maintenance->schedule($start, $end, $message));
        $this->assertEquals([
            'enabled' => false,
            'message' => $message,
            'start'   => $start->toIso8601String(),
            'end'     => $end->toIso8601String(),
        ], $storage->load());
    }

    /**
     * @covers ::stop
     */
    public function testStopScheduled(): void {
        $maintenance = Mockery::mock(Maintenance::class);
        $maintenance->shouldAllowMockingProtectedMethods();
        $maintenance->makePartial();
        $maintenance
            ->shouldReceive('isJobScheduled')
            ->with(DisableCronJob::class)
            ->once()
            ->andReturn(true);
        $maintenance
            ->shouldReceive('disable')
            ->never();

        Queue::fake();

        $this->assertTrue($maintenance->stop());

        Queue::assertNothingPushed();
    }

    /**
     * @covers ::stop
     */
    public function testStopNotScheduled(): void {
        $maintenance = Mockery::mock(Maintenance::class, [
            $this->app,
            $this->app->make(SettingsService::class),
            $this->app->make(QueueableConfigurator::class),
            $this->app->make(Storage::class),
        ]);
        $maintenance->shouldAllowMockingProtectedMethods();
        $maintenance->makePartial();
        $maintenance
            ->shouldReceive('isJobScheduled')
            ->with(DisableCronJob::class)
            ->once()
            ->andReturn(false);
        $maintenance
            ->shouldReceive('disable')
            ->never();

        Queue::fake();

        $this->assertTrue($maintenance->stop());

        Queue::assertPushed(DisableCronJob::class);
    }

    /**
     * @covers ::stop
     */
    public function testStopForce(): void {
        $maintenance = Mockery::mock(Maintenance::class);
        $maintenance->shouldAllowMockingProtectedMethods();
        $maintenance->makePartial();
        $maintenance
            ->shouldReceive('isJobScheduled')
            ->never();
        $maintenance
            ->shouldReceive('disable')
            ->once()
            ->andReturn(true);

        $this->assertTrue($maintenance->stop(true));
    }

    /**
     * @covers ::stop
     */
    public function testStart(): void {
        $maintenance = Mockery::mock(Maintenance::class, [
            $this->app,
            $this->app->make(SettingsService::class),
            $this->app->make(QueueableConfigurator::class),
            $this->app->make(Storage::class),
        ]);
        $maintenance->makePartial();
        $maintenance
            ->shouldReceive('isEnabled')
            ->once()
            ->andReturn(false);
        $maintenance
            ->shouldReceive('schedule')
            ->once()
            ->andReturn(true);

        Queue::fake();

        $this->assertTrue($maintenance->start(Date::now()));

        Queue::assertPushed(EnableCronJob::class);
    }

    /**
     * @covers ::stop
     */
    public function testStartIfEnabled(): void {
        $maintenance = Mockery::mock(Maintenance::class);
        $maintenance->makePartial();
        $maintenance
            ->shouldReceive('isEnabled')
            ->once()
            ->andReturn(true);
        $maintenance
            ->shouldReceive('schedule')
            ->never();

        $this->assertTrue($maintenance->start(Date::now()));
    }

    /**
     * @covers ::isJobScheduled
     * @dataProvider dataProviderIsJobScheduled
     *
     * @param array<string, mixed> $settings
     */
    public function testIsJobScheduled(bool $expected, array $settings): void {
        $job         = new class() implements Cronable {
            /**
             * @inheritDoc
             */
            public function getQueueConfig(): array {
                return [];
            }
        };
        $maintenance = new class($this->app, $this->app->make(QueueableConfigurator::class)) extends Maintenance {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Application $app,
                protected QueueableConfigurator $configurator,
            ) {
                // empty
            }

            public function isJobScheduled(string $job): bool {
                return parent::isJobScheduled($job);
            }
        };

        $this->setQueueableConfig($job, $settings);

        $this->assertEquals($expected, $maintenance->isJobScheduled($job::class));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{bool, array<string, mixed>}>
     */
    public function dataProviderIsJobScheduled(): array {
        return [
            'scheduled'               => [
                true,
                [
                    CronableConfig::Enabled => true,
                    CronableConfig::Cron    => '* * * * *',
                ],
            ],
            'scheduled (in interval)' => [
                true,
                [
                    CronableConfig::Enabled => true,
                    CronableConfig::Cron    => ltrim((new DateTime())->add(
                        new DateInterval('PT23H58M'),
                    )->format('i G j n w'), '0'),
                ],
            ],
            'in the future'           => [
                false,
                [
                    CronableConfig::Enabled => true,
                    CronableConfig::Cron    => ltrim((new DateTime())->add(
                        new DateInterval('P2D'),
                    )->format('i G j n w'), '0'),
                ],
            ],
            'disabled'                => [
                false,
                [
                    CronableConfig::Enabled => false,
                    CronableConfig::Cron    => '* * * * *',
                ],
            ],
            'no cron'                 => [
                false,
                [
                    CronableConfig::Enabled => true,
                    CronableConfig::Cron    => false,
                ],
            ],
            'invalid cron'            => [
                false,
                [
                    CronableConfig::Enabled => true,
                    CronableConfig::Cron    => 'invalid',
                ],
            ],
        ];
    }
    // </editor-fold>
}
