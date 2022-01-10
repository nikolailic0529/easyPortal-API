<?php declare(strict_types = 1);

namespace App\Rules;

use App\GraphQL\Directives\Directives\Mutation\Context\Context;
use App\GraphQL\Directives\Directives\Mutation\Context\EmptyContext;
use App\GraphQL\Directives\Directives\Mutation\Context\ResolverContext;
use App\Models\User;
use Closure;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\UserNotMe
 */
class UserNotMeTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::message
     */
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
        $this->assertEquals($this->app->make(UserNotMe::class)->message(), 'Translated');
    }

    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, Closure $userFactory, string $value): void {
        $this->setUser($userFactory);
        $this->assertEquals($expected, $this->app->make(UserNotMe::class)->passes('test', $value));
    }

    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPassesMutation
     */
    public function testPassesMutation(bool $expected, Closure $userFactory, Closure $contextFactory): void {
        $user    = $this->setUser($userFactory);
        $rule    = $this->app->make(UserNotMe::class);
        $context = $contextFactory($this, $user);

        $rule->setMutationContext($context);

        $this->assertEquals($expected, $rule->passes('test', $this->faker->word));
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
                    $user = User::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ]);

                    return $user;
                },
                'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
            ],
            'fail'    => [
                false,
                static function (): User {
                    $user = User::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ]);

                    return $user;
                },
                'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
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
                    return User::factory()->make([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ]);
                },
                static function (): Context {
                    return new ResolverContext(null, User::factory()->make());
                },
            ],
            'fail (same user)' => [
                false,
                static function (): User {
                    return User::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ]);
                },
                static function (self $test, User $user): Context {
                    return new ResolverContext(null, $user);
                },
            ],
            'fail (no user)'   => [
                false,
                static function (): User {
                    return User::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ]);
                },
                static function (): Context {
                    return new EmptyContext(null);
                },
            ],
        ];
    }
    // </editor-fold>
}
