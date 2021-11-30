<?php declare(strict_types = 1);

namespace App\Services\Maintenance;

use App\Services\Settings\Settings as SettingsService;
use Illuminate\Support\Facades\Date;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Maintenance\Maintenance
 */
class MaintenanceTest extends TestCase {
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
        $this->override(SettingsService::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('setEditableSettings')
                ->with([
                    'EP_MAINTENANCE_ENABLE_ENABLED'  => false,
                    'EP_MAINTENANCE_DISABLE_ENABLED' => false,
                ])
                ->once()
                ->andReturn([]);
        });

        $maintenance = $this->app->make(Maintenance::class);

        $this->assertTrue($maintenance->disable());
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
                    'EP_MAINTENANCE_ENABLE_ENABLED'  => true,
                    'EP_MAINTENANCE_ENABLE_CRON'     => '15 10 30 11 2',
                    'EP_MAINTENANCE_DISABLE_ENABLED' => true,
                    'EP_MAINTENANCE_DISABLE_CRON'    => '15 11 14 1 5',
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
}
