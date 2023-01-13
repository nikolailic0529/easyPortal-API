<?php declare(strict_types = 1);

namespace App\Rules\Organization;

use App\GraphQL\Directives\Directives\Mutation\Context\ResolverContext;
use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Translation\PotentiallyTranslatedString;
use Mockery;
use Tests\TestCase;

use function value;

/**
 * @internal
 * @covers \App\Rules\Organization\EmailInvitable
 *
 * @phpstan-type Expected array{passed: bool, messages: array<mixed>}
 */
class EmailInvitableTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param Expected                              $expected
     * @param Closure(static): ?Organization|null   $orgFactory
     * @param Closure(static, ?Organization): mixed $valueFactory
     */
    public function testInvoke(array $expected, Closure|null $orgFactory, Closure $valueFactory): void {
        // Prepare
        $this->setOrganization($this->setRootOrganization(Organization::factory()->make()));

        // Mock
        $this->app->instance('translator', value(static function (): Translator {
            $translator = Mockery::mock(Translator::class);
            $translator
                ->shouldReceive('get')
                ->andReturnUsing(static function (string $key): string {
                    return $key;
                });

            return $translator;
        }));

        // Test
        $org      = $orgFactory ? $orgFactory($this) : null;
        $rule     = $this->app->make(EmailInvitable::class)->setMutationContext(new ResolverContext(null, $org));
        $value    = $valueFactory($this, $org);
        $messages = [];

        $rule(
            'value',
            $value,
            static function (string $message) use (&$messages): PotentiallyTranslatedString {
                $messages[] = $message;
                $string     = Mockery::mock(PotentiallyTranslatedString::class);

                return $string;
            },
        );

        self::assertEquals($expected, [
            'passed'   => $messages === [],
            'messages' => $messages,
        ]);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{Expected,Closure(static): ?Organization|null,Closure(static, ?Organization): mixed}>
     */
    public function dataProviderInvoke(): array {
        return [
            'no organization'      => [
                [
                    'passed'   => false,
                    'messages' => [
                        'validation.email_invitable.organization_unknown',
                    ],
                ],
                null,
                static function (TestCase $test): string {
                    return $test->faker->email();
                },
            ],
            'user disabled'        => [
                [
                    'passed'   => false,
                    'messages' => [
                        'validation.email_invitable.user_disabled',
                    ],
                ],
                static function (): Organization {
                    return Organization::factory()->create();
                },
                static function (TestCase $test): string {
                    $user = User::factory()->create([
                        'email_verified' => true,
                        'enabled'        => false,
                    ]);

                    return $user->email;
                },
            ],
            'local user'           => [
                [
                    'passed'   => false,
                    'messages' => [
                        'validation.email_invitable.user_root',
                    ],
                ],
                static function (): Organization {
                    return Organization::factory()->create();
                },
                static function (): string {
                    $user = User::factory()->create([
                        'type' => UserType::local(),
                    ]);

                    return $user->email;
                },
            ],
            'invited user'         => [
                [
                    'passed'   => false,
                    'messages' => [
                        'validation.email_invitable.user_root',
                    ],
                ],
                static function (): Organization {
                    return Organization::factory()->create();
                },
                static function (TestCase $test, ?Organization $org): string {
                    $user = User::factory()->create([
                        'type' => UserType::local(),
                    ]);

                    OrganizationUser::factory()->create([
                        'organization_id' => $org,
                        'user_id'         => $user,
                        'invited'         => true,
                    ]);

                    return $user->email;
                },
            ],
            'invited'              => [
                [
                    'passed'   => false,
                    'messages' => [
                        'validation.email_invitable.user_member',
                    ],
                ],
                static function (): Organization {
                    return Organization::factory()->create();
                },
                static function (TestCase $test, ?Organization $org): string {
                    $user = User::factory()->create();

                    OrganizationUser::factory()->create([
                        'organization_id' => $org,
                        'user_id'         => $user,
                        'invited'         => false,
                    ]);

                    return $user->email;
                },
            ],
            'no organization user' => [
                [
                    'passed'   => true,
                    'messages' => [
                        // empty
                    ],
                ],
                static function (): Organization {
                    return Organization::factory()->create();
                },
                static function (): string {
                    $user = User::factory()->create();

                    return $user->email;
                },
            ],
            'passed'               => [
                [
                    'passed'   => true,
                    'messages' => [
                        // empty
                    ],
                ],
                static function (): Organization {
                    return Organization::factory()->create();
                },
                static function (TestCase $test, ?Organization $org): string {
                    $user = User::factory()->create();

                    OrganizationUser::factory()->create([
                        'organization_id' => $org,
                        'user_id'         => $user,
                        'invited'         => true,
                    ]);

                    return $user->email;
                },
            ],
        ];
    }
    // </editor-fold>
}
