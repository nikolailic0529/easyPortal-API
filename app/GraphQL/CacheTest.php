<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Services\I18n\Locale;
use App\Services\Organization\CurrentOrganization;
use App\Utils\Cache\CacheKey;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Date;
use LogicException;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Cache
 */
class CacheTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::isSlowQuery
     *
     * @dataProvider dataProviderIsSlowQuery
     */
    public function testIsSlowQuery(bool $expected, ?float $threshold, float $time): void {
        $this->setSettings([
            'ep.cache.graphql.threshold' => $threshold,
        ]);

        self::assertEquals($expected, $this->app->make(Cache::class)->isSlowQuery($time));
    }

    /**
     * @covers ::getExpired
     * @covers ::markExpired
     */
    public function testGetExpired(): void {
        $handler      = Mockery::mock(ExceptionHandler::class);
        $config       = $this->app->make(ConfigContract::class);
        $service      = $this->app->make(Service::class);
        $organization = $this->app->make(CurrentOrganization::class);
        $locale       = $this->app->make(Locale::class);
        $factory      = $this->app->make(CacheFactory::class);
        $cache        = new class($handler, $config, $service, $organization, $locale, $factory) extends Cache {
            public function getExpired(): ?DateTimeInterface {
                return parent::getExpired();
            }
        };

        self::assertNull($cache->getExpired());

        $cache->markExpired();

        self::assertInstanceOf(DateTimeInterface::class, $cache->getExpired());
    }

    /**
     * @covers ::isExpired
     *
     * @dataProvider dataProviderIsCacheExpired
     *
     * @param array<string,mixed> $settings
     */
    public function testIsExpired(
        bool $expected,
        string $now,
        array $settings,
        float $random,
        string $created,
        string $expired,
        ?string $marker,
    ): void {
        $created = Date::make($created);
        $expired = Date::make($expired);
        $marker  = Date::make($marker);

        $this->setSettings($settings);

        Date::setTestNow($now);

        $handler      = Mockery::mock(ExceptionHandler::class);
        $config       = $this->app->make(ConfigContract::class);
        $service      = $this->app->make(Service::class);
        $factory      = $this->app->make(CacheFactory::class);
        $organization = Mockery::mock(CurrentOrganization::class);
        $locale       = Mockery::mock(Locale::class);
        $cache        = Mockery::mock(Cache::class, [$handler, $config, $service, $organization, $locale, $factory]);
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

    /**
     * @covers ::lock
     */
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

    /**
     * @covers ::lock
     */
    public function testLockNotSupported(): void {
        $this->setSettings([
            'ep.cache.graphql.lock_enabled' => true,
        ]);

        $handler      = Mockery::mock(ExceptionHandler::class);
        $config       = $this->app->make(ConfigContract::class);
        $service      = $this->app->make(Service::class);
        $organization = Mockery::mock(CurrentOrganization::class);
        $locale       = Mockery::mock(Locale::class);
        $factory      = Mockery::mock(CacheFactory::class);
        $factory
            ->shouldReceive('store')
            ->once()
            ->andReturn(
                Mockery::mock(CacheContract::class),
            );

        $cache    = new class($handler, $config, $service, $organization, $locale, $factory) extends Cache {
            // empty
        };
        $callback = static function (): void {
            // empty
        };

        self::expectException(LogicException::class);

        $cache->lock('test', Closure::fromCallable($callback));
    }

    /**
     * @covers ::lock
     */
    public function testLock(): void {
        $graphqlStore = $this->faker->word();
        $lockTimeout  = $this->faker->randomNumber();
        $lockWait     = $this->faker->randomNumber();

        $this->setSettings([
            'ep.cache.graphql.store'        => $graphqlStore,
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
        $config       = $this->app->make(ConfigContract::class);
        $organization = $this->app->make(CurrentOrganization::class);
        $locale       = $this->app->make(Locale::class);
        $cache        = new Cache($handler, $config, $service, $organization, $locale, $cacheFactory);

        $cache->lock('test', $closure);

        $callback
            ->shouldHaveBeenCalled()
            ->once();
    }

    /**
     * @covers ::isLocked
     */
    public function testIsLockedDisabled(): void {
        $this->setSettings([
            'ep.cache.graphql.lock_enabled' => false,
        ]);

        $cache = $this->app->make(Cache::class);

        self::assertFalse($cache->isLocked('test'));
    }

    /**
     * @covers ::isLocked
     */
    public function testIsLockedNotSupported(): void {
        $this->setSettings([
            'ep.cache.graphql.lock_enabled' => true,
        ]);

        $handler      = Mockery::mock(ExceptionHandler::class);
        $config       = $this->app->make(ConfigContract::class);
        $service      = $this->app->make(Service::class);
        $organization = Mockery::mock(CurrentOrganization::class);
        $locale       = Mockery::mock(Locale::class);
        $factory      = Mockery::mock(CacheFactory::class);
        $factory
            ->shouldReceive('store')
            ->once()
            ->andReturn(
                Mockery::mock(CacheContract::class),
            );

        $cache = new class($handler, $config, $service, $organization, $locale, $factory) extends Cache {
            // empty
        };

        self::expectException(LogicException::class);

        $cache->isLocked('test');
    }

    /**
     * @covers ::isLocked
     */
    public function testIsLocked(): void {
        $this->setSettings([
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
            $this->app->make(Locale::class),
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
     * @return array<string,array{bool,?float,float}>
     */
    public function dataProviderIsSlowQuery(): array {
        return [
            'null' => [true, null, 0],
            'zero' => [true, 0.0, -1],
            'slow' => [true, 1, 1.034],
            'fast' => [false, 1, 0.034],
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
