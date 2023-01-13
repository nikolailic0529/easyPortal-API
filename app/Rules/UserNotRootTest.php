<?php declare(strict_types = 1);

namespace App\Rules;

use App\GraphQL\Directives\Directives\Mutation\Context\Context;
use App\GraphQL\Directives\Directives\Mutation\Context\EmptyContext;
use App\GraphQL\Directives\Directives\Mutation\Context\ResolverContext;
use App\Models\Enums\UserType;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\Factory;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Rules\UserNotRoot
 */
class UserNotRootTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    public function testMessage(): void {
        $this->app->setLocale('de');
        $translationsFactory = static function (TestCase $test, string $locale): array {
            return [
                $locale => [
                    'validation.user_not_root' => 'Translated',
                ],
            ];
        };
        $this->setTranslations($translationsFactory);
        self::assertEquals($this->app->make(UserNotRoot::class)->message(), 'Translated');
    }

    /**
     * @dataProvider dataProviderPasses
     *
     * @param Closure(static): User $userFactory
     */
    public function testPasses(bool $expected, Closure $userFactory): void {
        $user   = $userFactory($this)->getKey();
        $rule   = $this->app->make(UserNotRoot::class);
        $actual = $rule->passes('test', $user);
        $passes = !$this->app->make(Factory::class)
            ->make(['value' => $user], ['value' => $rule])
            ->fails();

        self::assertEquals($expected, $actual);
        self::assertEquals($expected, $passes);
    }

    /**
     * @dataProvider dataProviderPassesMutation
     *
     * @param Closure(static): ?Context $contextFactory
     */
    public function testPassesMutation(bool $expected, Closure $contextFactory): void {
        $rule    = $this->app->make(UserNotRoot::class);
        $context = $contextFactory($this);

        if ($context) {
            $rule->setMutationContext($context);
        }

        $value  = $this->faker->word();
        $actual = $rule->passes('test', $value);
        $passes = !$this->app->make(Factory::class)
            ->make(['value' => $value], ['value' => $rule])
            ->fails();

        self::assertEquals($expected, $actual);
        self::assertEquals($expected, $passes);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPasses(): array {
        return [
            'success'       => [
                true,
                static function (): User {
                    return User::factory()->create([
                        'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                        'type' => UserType::keycloak(),
                    ]);
                },
            ],
            'fail: root'    => [
                false,
                static function (): User {
                    return User::factory()->create([
                        'id'   => 'a961c0ce-f6a4-48d3-b59e-eb5f3025bb40',
                        'type' => UserType::local(),
                    ]);
                },
            ],
            'fail: no user' => [
                false,
                static function (): User {
                    return User::factory()->make([
                        'id'   => 'a961c0ce-f6a4-48d3-b59e-eb5f3025bb40',
                        'type' => UserType::keycloak(),
                    ]);
                },
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderPassesMutation(): array {
        return [
            'passes'        => [
                true,
                static function (): Context {
                    return new ResolverContext(null, User::factory()->make([
                        'type' => UserType::keycloak(),
                    ]));
                },
            ],
            'fail: root'    => [
                false,
                static function (): Context {
                    return new ResolverContext(null, User::factory()->make([
                        'type' => UserType::local(),
                    ]));
                },
            ],
            'fail: no user' => [
                false,
                static function (): Context {
                    return new EmptyContext(null);
                },
            ],
        ];
    }
    // </editor-fold>
}
