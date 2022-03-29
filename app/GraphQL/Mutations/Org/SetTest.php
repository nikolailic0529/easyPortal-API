<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AnyOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLValidationError;
use Tests\GraphQL\JsonFragment;
use Tests\GraphQL\JsonFragmentSchema;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Org\Set
 *
 * @phpstan-import-type OrganizationFactory from \Tests\WithOrganization
 * @phpstan-import-type UserFactory from \Tests\WithUser
 * @phpstan-type        InvokeInputFactory Closure(static, ?Organization, ?User): array{organization_id: ?string}
 */
class SetTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory $organizationFactory
     * @param UserFactory         $userFactory
     * @param InvokeInputFactory  $inputFactory
     */
    public function testInvoke(
        Response $expected,
        mixed $organizationFactory,
        mixed $userFactory = null,
        Closure $inputFactory = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        $input = $inputFactory
            ? $inputFactory($this, $organization, $user)
            : ['organization_id' => $this->faker->uuid()];
        $query = /** @lang GraphQL */
            <<<'GRAPHQL'
            mutation mutate($input: OrgSetInput!) {
                org {
                    set(input: $input){
                        result
                        org {
                            id
                        }
                    }
                }
            }
            GRAPHQL;

        // Test
        $this
            ->graphQL($query, ['input' => $input])
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{Response, OrganizationFactory, UserFactory, InvokeInputFactory}>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new AnyOrganizationDataProvider(),
            new UserDataProvider('org'),
            new ArrayDataProvider([
                'ok'                         => [
                    new GraphQLSuccess(
                        'org',
                        new JsonFragmentSchema('set', self::class),
                        new JsonFragment('set', [
                            'result' => true,
                            'org'    => [
                                'id' => 'eb2f569c-3f83-4f32-adb3-d49176b0fa81',
                            ],
                        ]),
                    ),
                    static function (TestCase $test, ?Organization $org, ?User $user): array {
                        $organization = Organization::factory()->create([
                            'id' => 'eb2f569c-3f83-4f32-adb3-d49176b0fa81',
                        ]);

                        if ($user) {
                            OrganizationUser::factory()->create([
                                'enabled'         => true,
                                'user_id'         => $user,
                                'organization_id' => $organization,
                            ]);
                        }

                        return [
                            'organization_id' => $organization->getKey(),
                        ];
                    },
                ],
                'user organization disabled' => [
                    new GraphQLSuccess(
                        'org',
                        new JsonFragmentSchema('set', self::class),
                        new JsonFragment('set.result', false),
                    ),
                    static function (TestCase $test, ?Organization $org, ?User $user): array {
                        $organization = Organization::factory()->create();

                        if ($user) {
                            OrganizationUser::factory()->create([
                                'enabled'         => false,
                                'user_id'         => $user,
                                'organization_id' => $organization,
                            ]);
                        }

                        return [
                            'organization_id' => $organization->getKey(),
                        ];
                    },
                ],
                'organization not exist'     => [
                    new GraphQLValidationError('org'),
                    static function (TestCase $test): array {
                        return [
                            'organization_id' => $test->faker->uuid(),
                        ];
                    },
                ],
                'organization is null'       => [
                    new GraphQLError('org'),
                    static function (): array {
                        return [
                            'organization_id' => null,
                        ];
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
