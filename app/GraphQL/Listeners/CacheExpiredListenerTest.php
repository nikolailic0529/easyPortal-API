<?php declare(strict_types = 1);

namespace App\GraphQL\Listeners;

use App\GraphQL\Cache;
use App\Models\Customer;
use App\Services\DataLoader\Collector\Data;
use App\Services\DataLoader\Events\DataImported;
use App\Services\I18n\Events\TranslationsUpdated;
use App\Services\Maintenance\Events\VersionUpdated;
use App\Services\Recalculator\Events\ModelsRecalculated;
use App\Services\Settings\Events\SettingsUpdated;
use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\GraphQL\Listeners\CacheExpiredListener
 */
class CacheExpiredListenerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderSubscribe
     *
     * @param Closure(self): object $eventFactory
     */
    public function testSubscribe(Closure $eventFactory): void {
        $this->override(CacheExpiredListener::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('__invoke')
                ->once()
                ->andReturns();
        });

        $this->app->make(Dispatcher::class)
            ->dispatch($eventFactory($this));
    }

    public function testInvoke(): void {
        $this->override(Cache::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('markExpired')
                ->once()
                ->andReturnSelf();
        });

        $listener = $this->app->make(CacheExpiredListener::class);

        $listener();
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array<Closure(): object>>
     */
    public function dataProviderSubscribe(): array {
        return [
            DataImported::class        => [
                static function (): object {
                    return new DataImported(new Data());
                },
            ],
            VersionUpdated::class      => [
                static function (): object {
                    return new VersionUpdated('1.0.0', null);
                },
            ],
            ModelsRecalculated::class  => [
                static function (self $test): object {
                    return new ModelsRecalculated(Customer::class, [
                        $test->faker->uuid(),
                    ]);
                },
            ],
            SettingsUpdated::class     => [
                static function (): object {
                    return new SettingsUpdated();
                },
            ],
            TranslationsUpdated::class => [
                static function (): object {
                    return new TranslationsUpdated();
                },
            ],
        ];
    }
    // </editor-fold>
}
