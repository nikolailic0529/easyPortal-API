<?php declare(strict_types = 1);

namespace App\Rules\Org;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Translation\PotentiallyTranslatedString;
use Mockery;
use Tests\TestCase;
use Tests\WithOrganization;

use function value;

/**
 * @internal
 * @covers \App\Rules\Org\EmailInvitable
 *
 * @phpstan-type Expected array{passed: bool, messages: array<mixed>}
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 */
class EmailInvitableTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param Expected                              $expected
     * @param OrganizationFactory                   $orgFactory
     * @param Closure(static, ?Organization): mixed $valueFactory
     */
    public function testInvoke(array $expected, mixed $orgFactory, Closure $valueFactory): void {
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
        $org      = $this->setOrganization($orgFactory);
        $rule     = $this->app->make(EmailInvitable::class);
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
     * @return array<string, array{Expected, OrganizationFactory, Closure(static, ?Organization): mixed}>
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
