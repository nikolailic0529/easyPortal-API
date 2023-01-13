<?php declare(strict_types = 1);

namespace App\Rules;

use App\GraphQL\Directives\Directives\Mutation\Context\Context;
use App\GraphQL\Directives\Directives\Mutation\Context\EmptyContext;
use App\GraphQL\Directives\Directives\Mutation\Context\ResolverContext;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\Factory;
use Tests\TestCase;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\Rules\UserNotMe
 *
 * @phpstan-import-type UserFactory from WithUser
 */
class UserNotMeTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    public function testMessage(): void {
        $this->app->setLocale('de');
        $translationsFactory = static function (TestCase $test, string $locale): array {
            return [
                $locale => [
                    'validation.user_not_me' => 'Translated',
                ],
            ];
        };
        $this->setTranslations($translationsFactory);
        self::assertEquals($this->app->make(UserNotMe::class)->message(), 'Translated');
    }

    /**
     * @dataProvider dataProviderPasses
     *
     * @param UserFactory $userFactory
     */
    public function testPasses(bool $expected, mixed $userFactory, string $value): void {
        $this->setUser($userFactory);

        $rule   = $this->app->make(UserNotMe::class);
        $actual = $rule->passes('test', $value);
        $passes = !$this->app->make(Factory::class)
            ->make(['value' => $value], ['value' => $rule])
            ->fails();

        self::assertEquals($expected, $actual);
        self::assertEquals($expected, $passes);
    }

    /**
     * @dataProvider dataProviderPassesMutation
     *
     * @param UserFactory                      $userFactory
     * @param Closure(static, ?User): ?Context $contextFactory
     */
    public function testPassesMutation(bool $expected, mixed $userFactory, Closure $contextFactory): void {
        $user    = $this->setUser($userFactory);
        $rule    = $this->app->make(UserNotMe::class);
        $context = $contextFactory($this, $user);

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
            'success' => [
                true,
                static function (): User {
                    return User::factory()->create();
                },
                'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
            ],
            'fail'    => [
                false,
                static function (): User {
                    return User::factory()->create([
                        'id' => 'a961c0ce-f6a4-48d3-b59e-eb5f3025bb40',
                    ]);
                },
                'a961c0ce-f6a4-48d3-b59e-eb5f3025bb40',
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderPassesMutation(): array {
        return [
            'passes'           => [
                true,
                static function (): User {
                    return User::factory()->make();
                },
                static function (): Context {
                    return new ResolverContext(null, User::factory()->make());
                },
            ],
            'fail (same user)' => [
                false,
                static function (): User {
                    return User::factory()->create();
                },
                static function (self $test, User $user): Context {
                    return new ResolverContext(null, $user);
                },
            ],
            'fail (no user)'   => [
                false,
                static function (): User {
                    return User::factory()->create();
                },
                static function (): Context {
                    return new EmptyContext(null);
                },
            ],
        ];
    }
    // </editor-fold>
}
