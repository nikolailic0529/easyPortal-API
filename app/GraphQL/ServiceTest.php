<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Models\Enums\UserType;
use App\Models\User;
use App\Services\I18n\Locale;
use Closure;
use GraphQL\Type\Introspection;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

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

        $this->assertEquals([$locale], $service->getDefaultKey());
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
        $success  = new GraphQLSuccess('__schema', null);
        $failed   = new GraphQLError('__schema');
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
    // </editor-fold>
}
