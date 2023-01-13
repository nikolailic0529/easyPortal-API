<?php declare(strict_types = 1);

namespace App\Services\Maintenance;

use App\Services\Maintenance\Jobs\CompleteCronJob;
use App\Services\Maintenance\Jobs\StartCronJob;
use App\Services\Settings\Settings as SettingsService;
use DateInterval;
use DateTime;
use Illuminate\Contracts\Config\Repository;
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
 * @covers \App\Services\Maintenance\Maintenance
 */
class MaintenanceTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    public function testGetSettings(): void {
        $maintenance = $this->app->make(Maintenance::class);
        $settings    = new Settings([
            'start'   => Date::now()->startOfDay(),
            'message' => $this->faker->sentence(),
        ]);
        $storage     = $this->app->make(Storage::class);

        self::assertTrue($storage->save($settings->toArray()));
        self::assertEquals($settings, $maintenance->getSettings());
    }

    public function testGetSettingsDownForMaintenance(): void {
        $this->override(Application::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('isDownForMaintenance')
                ->once()
                ->andReturn(true);
        });

        $maintenance = $this->app->make(Maintenance::class);
        $expected    = new Settings([
            'enabled' => true,
        ]);

        self::assertEquals($expected, $maintenance->getSettings());
    }

    public function testIsEnabled(): void {
        $maintenance = $this->app->make(Maintenance::class);
        $settings    = new Settings([
            'enabled' => $this->faker->boolean(),
        ]);
        $storage     = $this->app->make(Storage::class);

        self::assertTrue($storage->save($settings->toArray()));
        self::assertEquals($settings->enabled, $maintenance->isEnabled());
    }

    public function testIsEnabledDownForMaintenance(): void {
        $this->override(Application::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('isDownForMaintenance')
                ->once()
                ->andReturn(true);
        });

        $maintenance = $this->app->make(Maintenance::class);

        self::assertTrue($maintenance->isEnabled());
    }

    public function testEnable(): void {
        $maintenance = $this->app->make(Maintenance::class);
        $settings    = new Settings([
            'enabled' => false,
        ]);
        $storage     = $this->app->make(Storage::class);

        self::assertTrue($storage->save($settings->toArray()));
        self::assertFalse($maintenance->isEnabled());
        self::assertTrue($maintenance->enable());
        self::assertTrue($maintenance->isEnabled());
    }

    public function testDisable(): void {
        $maintenance = $this->app->make(Maintenance::class);
        $storage     = $this->app->make(Storage::class);
        $data        = ['enabled' => true];

        self::assertTrue($storage->save($data));
        self::assertEquals($data, $storage->load());
        self::assertTrue($maintenance->disable());
        self::assertEquals([], $storage->load());
    }

    public function testSchedule(): void {
        $message = $this->faker->sentence();
        $start   = Date::make('2021-11-30T10:15:00+00:00');
        $end     = Date::make('2022-01-14T11:15:22+00:00');

        $this->setSettings([
            'ep.maintenance.notify.before' => 'P2D',
        ]);

        $this->override(SettingsService::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('setEditableSettings')
                ->with([
                    'EP_MAINTENANCE_START_CRON'    => '15 10 30 11 2',
                    'EP_MAINTENANCE_NOTIFY_CRON'   => '15 10 28 11 0',
                    'EP_MAINTENANCE_COMPLETE_CRON' => '15 11 14 1 5',
                ])
                ->once()
                ->andReturn([]);
        });

        $maintenance = $this->app->make(Maintenance::class);
        $storage     = $this->app->make(Storage::class);

        self::assertTrue($maintenance->schedule($start, $end, $message));
        self::assertEquals([
            'notified' => false,
            'enabled'  => false,
            'message'  => $message,
            'start'    => $start->toIso8601String(),
            'end'      => $end->toIso8601String(),
        ], $storage->load());
    }

    public function testStopScheduled(): void {
        $maintenance = Mockery::mock(Maintenance::class);
        $maintenance->shouldAllowMockingProtectedMethods();
        $maintenance->makePartial();
        $maintenance
            ->shouldReceive('isJobScheduled')
            ->with(CompleteCronJob::class)
            ->once()
            ->andReturn(true);
        $maintenance
            ->shouldReceive('disable')
            ->never();

        Queue::fake();

        self::assertTrue($maintenance->stop());

        Queue::assertNothingPushed();
    }

    public function testStopNotScheduled(): void {
        $maintenance = Mockery::mock(Maintenance::class, [
            $this->app,
            $this->app->make(Repository::class),
            $this->app->make(SettingsService::class),
            $this->app->make(QueueableConfigurator::class),
            $this->app->make(Storage::class),
        ]);
        $maintenance->shouldAllowMockingProtectedMethods();
        $maintenance->makePartial();
        $maintenance
            ->shouldReceive('isJobScheduled')
            ->with(CompleteCronJob::class)
            ->once()
            ->andReturn(false);
        $maintenance
            ->shouldReceive('disable')
            ->never();

        Queue::fake();

        self::assertTrue($maintenance->stop());

        Queue::assertPushed(CompleteCronJob::class);
    }

    public function testStopForce(): void {
        $maintenance = Mockery::mock(Maintenance::class, [
            $this->app,
            $this->app->make(Repository::class),
            $this->app->make(SettingsService::class),
            $this->app->make(QueueableConfigurator::class),
            $this->app->make(Storage::class),
        ]);
        $maintenance->shouldAllowMockingProtectedMethods();
        $maintenance->makePartial();
        $maintenance
            ->shouldReceive('isJobScheduled')
            ->never();
        $maintenance
            ->shouldReceive('disable')
            ->once()
            ->andReturn(true);

        Queue::fake();

        self::assertTrue($maintenance->stop(true));

        Queue::assertNothingPushed();
    }

    public function testStart(): void {
        $maintenance = Mockery::mock(Maintenance::class, [
            $this->app,
            $this->app->make(Repository::class),
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

        self::assertTrue($maintenance->start(Date::now()));

        Queue::assertPushed(StartCronJob::class);
    }

    public function testStartForce(): void {
        $maintenance = Mockery::mock(Maintenance::class, [
            $this->app,
            $this->app->make(Repository::class),
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
        $maintenance
            ->shouldReceive('enable')
            ->once()
            ->andReturn(true);

        Queue::fake();

        self::assertTrue($maintenance->start(Date::now(), null, true));

        Queue::assertNothingPushed();
    }

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

        self::assertTrue($maintenance->start(Date::now()));
    }

    /**
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

        self::assertEquals($expected, $maintenance->isJobScheduled($job::class));
    }

    public function testMarkAsNotified(): void {
        $maintenance = $this->app->make(Maintenance::class);
        $settings    = new Settings();
        $storage     = $this->app->make(Storage::class);

        self::assertTrue($storage->save($settings->toArray()));
        self::assertTrue($maintenance->markAsNotified());
        self::assertTrue($maintenance->getSettings()->notified ?? null);
    }

    public function testMarkAsNotifiedNoSettings(): void {
        $maintenance = $this->app->make(Maintenance::class);

        self::assertFalse($maintenance->markAsNotified());
        self::assertNull($maintenance->getSettings()->notified ?? null);
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
                        new DateInterval('PT1H'),
                    )->format('* G j n w'), '0'),
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
