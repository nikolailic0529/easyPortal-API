<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Services\I18n\CurrentLocale;
use App\Services\Organization\CurrentOrganization;
use App\Utils\Cache\CacheKey;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Date;
use Mockery;
use Tests\TestCase;
use Tests\WithSettings;

/**
 * @internal
 * @covers \App\GraphQL\Cache
 *
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class CacheTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderIsQuerySlow
     */
    public function testIsQuerySlow(bool $expected, ?float $threshold, ?float $time): void {
        $this->setSettings([
            'ep.cache.graphql.threshold' => $threshold,
        ]);

        self::assertEquals($expected, $this->app->make(Cache::class)->isQuerySlow($time));
    }

    /**
     * @dataProvider dataProviderIsQueryLockable
     */
    public function testIsQueryLockable(bool $expected, ?float $threshold, ?float $time): void {
        $this->setSettings([
            'ep.cache.graphql.lock_threshold' => $threshold,
        ]);

        self::assertEquals($expected, $this->app->make(Cache::class)->isQueryLockable($time));
    }

    public function testGetExpired(): void {
        $this->setSettings([
            'ep.cache.graphql.enabled' => true,
        ]);

        $handler      = Mockery::mock(ExceptionHandler::class);
        $service      = $this->app->make(Service::class);
        $organization = $this->app->make(CurrentOrganization::class);
        $locale       = $this->app->make(CurrentLocale::class);
        $factory      = $this->app->make(CacheFactory::class);
        $cache        = new class($handler, $service, $organization, $locale, $factory) extends Cache {
            public function getExpired(): ?DateTimeInterface {
                return parent::getExpired();
            }
        };

        self::assertNull($cache->getExpired());

        $cache->markExpired();

        self::assertInstanceOf(DateTimeInterface::class, $cache->getExpired());
    }

    public function testGetExpiredDisabled(): void {
        $this->setSettings([
            'ep.cache.graphql.enabled' => false,
        ]);

        $handler      = Mockery::mock(ExceptionHandler::class);
        $service      = $this->app->make(Service::class);
        $organization = $this->app->make(CurrentOrganization::class);
        $locale       = $this->app->make(CurrentLocale::class);
        $factory      = $this->app->make(CacheFactory::class);
        $cache        = new class($handler, $service, $organization, $locale, $factory) extends Cache {
            public function getExpired(): ?DateTimeInterface {
                return parent::getExpired();
            }
        };

        self::assertNull($cache->getExpired());

        $cache->markExpired();

        self::assertNull($cache->getExpired());
    }

    /**
     * @dataProvider dataProviderIsCacheExpired
     *
     * @param SettingsFactory $settingsFactory
     */
    public function testIsExpired(
        bool $expected,
        string $now,
        mixed $settingsFactory,
        float $random,
        string $created,
        string $expired,
        ?string $marker,
    ): void {
        $created = Date::make($created);
        $expired = Date::make($expired);
        $marker  = Date::make($marker);

        $this->setSettings($settingsFactory);

        Date::setTestNow($now);

        $handler      = Mockery::mock(ExceptionHandler::class);
        $service      = $this->app->make(Service::class);
        $factory      = $this->app->make(CacheFactory::class);
        $organization = Mockery::mock(CurrentOrganization::class);
        $locale       = Mockery::mock(CurrentLocale::class);
        $cache        = Mockery::mock(Cache::class, [$handler, $service, $organization, $locale, $factory]);
        $cache->shouldAllowMockingProtectedMethods();
        $cache->makePartial();
        $cache
            ->shouldReceive('getRandomNumber')
            ->andReturn($random);
        $cache
            ->shouldReceive('getExpired')
            ->andReturn($marker);

        self::assertEquals($expected, $cache->isExpired($created, $expired));
    }

    public function testLockDisabled(): void {
        $this->setSettings([
            'ep.cache.graphql.lock_enabled' => false,
        ]);

        $cache    = $this->app->make(Cache::class);
        $callback = Mockery::spy(static function (): void {
            // empty
        });

        $cache->lock('test', Closure::fromCallable($callback));

        $callback
            ->shouldHaveBeenCalled()
            ->once();
    }

    public function testLockNotSupported(): void {
        $this->setSettings([
            'ep.cache.graphql.enabled'      => true,
            'ep.cache.graphql.lock_enabled' => true,
        ]);

        $handler = Mockery::mock(ExceptionHandler::class);
        $service = $this->app->make(Service::class);
        $locale  = Mockery::mock(CurrentLocale::class);
        $org     = Mockery::mock(CurrentOrganization::class);

        $store = Mockery::mock(CacheContract::class);
        $store
            ->shouldReceive('getStore')
            ->once()
            ->andReturn(null);

        $factory = Mockery::mock(CacheFactory::class);
        $factory
            ->shouldReceive('store')
            ->once()
            ->andReturn($store);

        $cache    = new class($handler, $service, $org, $locale, $factory) extends Cache {
            // empty
        };
        $callback = static function (): void {
            // empty
        };

        $cache->lock('test', Closure::fromCallable($callback));
    }

    public function testLock(): void {
        $graphqlStore = $this->faker->word();
        $lockTimeout  = $this->faker->randomNumber();
        $lockWait     = $this->faker->randomNumber();

        $this->setSettings([
            'ep.cache.graphql.store'        => $graphqlStore,
            'ep.cache.graphql.enabled'      => true,
            'ep.cache.graphql.lock_enabled' => true,
            'ep.cache.graphql.lock_timeout' => "PT{$lockTimeout}S",
            'ep.cache.graphql.lock_wait'    => "PT{$lockWait}S",
        ]);

        $callback = Mockery::spy(static function (): void {
            // empty
        });
        $closure  = Closure::fromCallable($callback);

        $lock = Mockery::mock(Lock::class);
        $lock
            ->shouldReceive('block')
            ->with($lockWait, $closure)
            ->once()
            ->andThrow(new LockTimeoutException());
        $lock
            ->shouldReceive('forceRelease')
            ->once()
            ->andReturns();

        $store = Mockery::mock(LockProvider::class);
        $store
            ->shouldReceive('lock')
            ->with(Mockery::any(), $lockTimeout)
            ->once()
            ->andReturn($lock);

        $cacheStore = Mockery::mock(CacheContract::class);
        $cacheStore
            ->shouldReceive('getStore')
            ->once()
            ->andReturn($store);

        $cacheFactory = Mockery::mock(CacheFactory::class);
        $cacheFactory
            ->shouldReceive('store')
            ->with($graphqlStore)
            ->once()
            ->andReturn($cacheStore);

        $handler      = Mockery::mock(ExceptionHandler::class);
        $service      = $this->app->make(Service::class);
        $organization = $this->app->make(CurrentOrganization::class);
        $locale       = $this->app->make(CurrentLocale::class);
        $cache        = new Cache($handler, $service, $organization, $locale, $cacheFactory);

        $cache->lock('test', $closure);

        $callback
            ->shouldHaveBeenCalled()
            ->once();
    }

    public function testIsLockedDisabled(): void {
        $this->setSettings([
            'ep.cache.graphql.lock_enabled' => false,
        ]);

        $cache = $this->app->make(Cache::class);

        self::assertFalse($cache->isLocked('test'));
    }

    public function testIsLockedNotSupported(): void {
        $this->setSettings([
            'ep.cache.graphql.enabled'      => true,
            'ep.cache.graphql.lock_enabled' => true,
        ]);

        $handler = Mockery::mock(ExceptionHandler::class);
        $service = $this->app->make(Service::class);
        $locale  = Mockery::mock(CurrentLocale::class);
        $org     = Mockery::mock(CurrentOrganization::class);

        $store = Mockery::mock(CacheContract::class);
        $store
            ->shouldReceive('getStore')
            ->once()
            ->andReturn(null);

        $factory = Mockery::mock(CacheFactory::class);
        $factory
            ->shouldReceive('store')
            ->once()
            ->andReturn($store);

        $cache = new class($handler, $service, $org, $locale, $factory) extends Cache {
            // empty
        };

        self::assertFalse($cache->isLocked('test'));
    }

    public function testIsLocked(): void {
        $this->setSettings([
            'ep.cache.graphql.enabled'      => true,
            'ep.cache.graphql.lock_enabled' => true,
        ]);

        $key   = 'test';
        $cache = $this->app->make(Cache::class);

        // No lock
        self::assertFalse($cache->isLocked($key));

        // Add Lock
        $store    = $this->app->make(CacheContract::class)->getStore();
        $cacheKey = (string) new CacheKey([
            'app',
            Service::getServiceName($cache),
            $this->app->make(CurrentLocale::class),
            $this->app->make(CurrentOrganization::class),
            $key,
        ]);

        self::assertInstanceOf(LockProvider::class, $store);

        $lock = $store->lock($cacheKey);

        $lock->get();

        self::assertTrue($cache->isLocked($key));

        // Release
        $lock->release();

        self::assertFalse($cache->isLocked($key));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{bool,?float,?float}>
     */
    public function dataProviderIsQuerySlow(): array {
        return [
            'null'    => [true, null, 0],
            'zero'    => [true, 0.0, -1],
            'slow'    => [true, 1, 1.034],
            'fast'    => [false, 1, 0.034],
            'unknown' => [false, 1, null],
        ];
    }

    /**
     * @return array<string,array{bool,?float,?float}>
     */
    public function dataProviderIsQueryLockable(): array {
        return [
            'null'    => [true, null, 0],
            'zero'    => [true, 0.0, -1],
            'slow'    => [true, 1, 1.034],
            'fast'    => [false, 1, 0.034],
            'unknown' => [false, 1, null],
        ];
    }

    /**
     * @return array<string, array{bool,?string,array<string,mixed>,float,string,string,?string}>
     */
    public function dataProviderIsCacheExpired(): array {
        return [
            'ttl expired'               => [
                true,
                '2021-02-09T12:00:00+00:00',
                [],
                0,
                '2021-02-08T12:00:00+00:00',
                '2021-02-09T12:00:00+00:00',
                null,
            ],
            'ttl expiring (hit)'        => [
                true,
                '2021-02-09T12:00:00+00:00',
                [
                    'ep.cache.graphql.ttl_expiration' => 'PT1H',
                ],
                0.25,
                '2021-02-08T12:00:00+00:00',
                '2021-02-09T12:30:00+00:00',
                null,
            ],
            'ttl expiring (miss)'       => [
                false,
                '2021-02-09T12:00:00+00:00',
                [
                    'ep.cache.graphql.ttl_expiration' => 'PT1H',
                ],
                0.75,
                '2021-02-08T12:00:00+00:00',
                '2021-02-09T12:30:00+00:00',
                null,
            ],
            'life too short'            => [
                false,
                '2021-02-09T12:00:00+00:00',
                [
                    'ep.cache.graphql.ttl_expiration' => 'P1D',
                    'ep.cache.graphql.lifetime'       => 'PT1H',
                ],
                1,
                '2021-02-09T11:30:00+00:00',
                '2021-02-10T00:00:00+00:00',
                '2021-02-09T12:00:00+00:00',
            ],
            'no expire date'            => [
                false,
                '2021-02-09T12:00:00+00:00',
                [
                    'ep.cache.graphql.ttl_expiration' => 'P1D',
                    'ep.cache.graphql.lifetime'       => 'PT1M',
                ],
                1,
                '2021-02-09T11:30:00+00:00',
                '2021-02-10T00:00:00+00:00',
                null,
            ],
            'created after expire date' => [
                false,
                '2021-02-09T12:00:00+00:00',
                [
                    'ep.cache.graphql.ttl_expiration' => 'P1D',
                    'ep.cache.graphql.lifetime'       => 'PT1M',
                ],
                1,
                '2021-02-09T11:30:00+00:00',
                '2021-02-10T00:00:00+00:00',
                '2021-02-09T11:00:00+00:00',
            ],
            'lifetime expired'          => [
                true,
                '2021-02-09T12:00:00+00:00',
                [
                    'ep.cache.graphql.ttl_expiration'      => 'P1D',
                    'ep.cache.graphql.lifetime_expiration' => 'PT1H',
                ],
                0.25,
                '2021-02-09T10:30:00+00:00',
                '2021-02-10T00:00:00+00:00',
                '2021-02-09T00:00:00+00:00',
            ],
            'lifetime expiring (hit)'   => [
                true,
                '2021-02-09T12:00:00+00:00',
                [
                    'ep.cache.graphql.ttl_expiration'      => 'P1D',
                    'ep.cache.graphql.lifetime'            => 'PT1H',
                    'ep.cache.graphql.lifetime_expiration' => 'PT1H',
                ],
                0.25,
                '2021-02-09T10:30:00+00:00',
                '2021-02-12T00:00:00+00:00',
                '2021-02-09T11:00:00+00:00',
            ],
            'lifetime expiring (miss)'  => [
                false,
                '2021-02-09T12:00:00+00:00',
                [
                    'ep.cache.graphql.ttl_expiration'      => 'P1D',
                    'ep.cache.graphql.lifetime'            => 'PT1H',
                    'ep.cache.graphql.lifetime_expiration' => 'PT1H',
                ],
                0.75,
                '2021-02-09T10:30:00+00:00',
                '2021-02-12T00:00:00+00:00',
                '2021-02-09T11:00:00+00:00',
            ],
        ];
    }
    // </editor-fold>
}
