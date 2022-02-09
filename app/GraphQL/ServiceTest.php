<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Models\Enums\UserType;
use App\Models\User;
use App\Services\I18n\Locale;
use Closure;
use DateTimeInterface;
use GraphQL\Type\Introspection;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Forbidden;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Ok;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use LogicException;
use Mockery;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function array_map;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Service
 */
class ServiceTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::getDefaultKey
     */
    public function testGetDefaultKey(): void {
        $locale  = $this->app->get(Locale::class);
        $service = new class($locale) extends Service {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Locale $locale,
            ) {
                // empty
            }

            /**
             * @inheritDoc
             */
            public function getDefaultKey(): array {
                return parent::getDefaultKey();
            }
        };

        $this->assertEquals([], $service->getDefaultKey());
    }

    /**
     * @covers ::isSlowQuery
     *
     * @dataProvider dataProviderIsSlowQuery
     */
    public function testIsSlowQuery(bool $expected, ?float $threshold, float $time): void {
        $this->setSettings([
            'ep.cache.graphql.threshold' => $threshold,
        ]);

        $this->assertEquals($expected, $this->app->make(Service::class)->isSlowQuery($time));
    }

    /**
     * @dataProvider dataProviderIntrospection
     */
    public function testIntrospection(
        Response $expected,
        Closure $settingsFactory,
        Closure $userFactory,
    ): void {
        $this->setSettings($settingsFactory);
        $this->setUser($userFactory);

        $this
            ->graphQL(Introspection::getIntrospectionQuery())
            ->assertThat($expected);
    }

    /**
     * @dataProvider dataProviderPlayground
     */
    public function testPlayground(
        Response $expected,
        Closure $settingsFactory,
        Closure $userFactory,
    ): void {
        $this->setSettings($settingsFactory);
        $this->setUser($userFactory);

        $this
            ->get('/graphql-playground')
            ->assertThat($expected);
    }

    /**
     * @covers ::markCacheExpired
     * @covers ::getCacheExpired
     */
    public function testCacheExpired(): void {
        $cache   = $this->app->make(Cache::class);
        $config  = $this->app->make(Config::class);
        $service = new class($config, $cache) extends Service {
            public function getCacheExpired(): ?DateTimeInterface {
                return parent::getCacheExpired();
            }
        };

        $this->assertNull($service->getCacheExpired());

        $service->markCacheExpired();

        $this->assertInstanceOf(DateTimeInterface::class, $service->getCacheExpired());
    }

    /**
     * @covers ::isCacheExpired
     *
     * @dataProvider dataProviderIsCacheExpired
     *
     * @param array<string,mixed> $settings
     */
    public function testIsCacheExpired(
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

        $cache   = $this->app->make(Cache::class);
        $config  = $this->app->make(Config::class);
        $service = Mockery::mock(Service::class, [$config, $cache]);
        $service->shouldAllowMockingProtectedMethods();
        $service->makePartial();
        $service
            ->shouldReceive('getRandomNumber')
            ->andReturn($random);
        $service
            ->shouldReceive('getCacheExpired')
            ->andReturn($marker);

        $this->assertEquals($expected, $service->isCacheExpired($created, $expired));
    }

    /**
     * @covers ::lock
     */
    public function testLockDisabled(): void {
        $this->setSettings([
            'ep.cache.graphql.lock_enabled' => false,
        ]);

        $cache    = Mockery::mock(Cache::class);
        $config   = $this->app->make(Config::class);
        $service  = new Service($config, $cache);
        $callback = Mockery::spy(static function (): void {
            // empty
        });

        $service->lock('test', Closure::fromCallable($callback));

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

        $cache    = Mockery::mock(Cache::class);
        $config   = $this->app->make(Config::class);
        $service  = new Service($config, $cache);
        $callback = static function (): void {
            // empty
        };

        $this->expectException(LogicException::class);

        $service->lock('test', Closure::fromCallable($callback));
    }

    /**
     * @covers ::lock
     */
    public function testLock(): void {
        $lockTimeout = $this->faker->randomNumber();
        $lockWait    = $this->faker->randomNumber();

        $this->setSettings([
            'ep.cache.graphql.lock_enabled' => true,
            'ep.cache.graphql.lock_timeout' => $lockTimeout,
            'ep.cache.graphql.lock_wait'    => $lockWait,
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

        $cache = Mockery::mock(Cache::class);
        $cache
            ->shouldReceive('getStore')
            ->once()
            ->andReturn($store);

        $config  = $this->app->make(Config::class);
        $service = new Service($config, $cache);

        $service->lock('test', $closure);

        $callback
            ->shouldHaveBeenCalled()
            ->once();
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
     * @return array<mixed>
     */
    public function dataProviderIntrospection(): array {
        $data = $this->dataProviderPlayground();
        $data = array_map(
            static function (array $case): array {
                $case[0] = $case[0] instanceof Ok
                    ? new GraphQLSuccess('__schema', null)
                    : new GraphQLError('__schema');

                return $case;
            },
            $data,
        );

        return $data;
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderPlayground(): array {
        $success  = new Ok();
        $failed   = new Forbidden();
        $enabled  = static function (): array {
            return [
                'app.debug' => true,
            ];
        };
        $disabled = static function (): array {
            return [
                'app.debug' => false,
            ];
        };
        $guest    = static function (): ?User {
            return null;
        };
        $user     = static function (): ?User {
            return User::factory()->create();
        };
        $root     = static function (): ?User {
            return User::factory()->create([
                'type' => UserType::local(),
            ]);
        };

        return (new MergeDataProvider([
            'debug on'  => new ArrayDataProvider([
                'guest' => [
                    $success,
                    $enabled,
                    $guest,
                ],
                'user'  => [
                    $success,
                    $enabled,
                    $user,
                ],
                'root'  => [
                    $success,
                    $enabled,
                    $root,
                ],
            ]),
            'debug off' => new ArrayDataProvider([
                'guest' => [
                    $failed,
                    $disabled,
                    $guest,
                ],
                'user'  => [
                    $failed,
                    $disabled,
                    $user,
                ],
                'root'  => [
                    $success,
                    $disabled,
                    $root,
                ],
            ]),
        ]))->getData();
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
                    'ep.cache.graphql.lifetime' => 'PT1H',
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
                    'ep.cache.graphql.lifetime' => 'PT1M',
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
                    'ep.cache.graphql.lifetime' => 'PT1M',
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
