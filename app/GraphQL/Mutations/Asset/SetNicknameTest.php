<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Asset;

use App\GraphQL\Directives\Directives\Mutation\Exceptions\ObjectNotFound;
use App\Models\Asset;
use App\Models\Organization;
use App\Models\User;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLValidationError;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;
use Throwable;

use function trans;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\Asset\SetNickname
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class SetNicknameTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory                          $orgFactory
     * @param UserFactory                                  $userFactory
     * @param Closure(static, ?Organization, ?User): Asset $prepare
     * @param array<string, mixed>|null                    $input
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $prepare = null,
        array $input = null,
    ): void {
        $org     = $this->setOrganization($orgFactory);
        $user    = $this->setUser($userFactory, $org);
        $assetId = $this->faker->uuid();
        $input ??= [
            'nickname' => $this->faker->word(),
        ];

        if ($prepare) {
            $assetId = $prepare($this, $org, $user)->getKey();
        } elseif ($org) {
            Asset::factory()->ownedBy($org)->create([
                'id' => $assetId,
            ]);
        } else {
            // empty
        }

        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation test($id: ID!, $input: AssetSetNicknameInput!) {
                    asset(id: $id) {
                        setNickname(input: $input) {
                            result
                            asset {
                                nickname
                            }
                        }
                    }
                }
                GRAPHQL,
                [
                    'id'    => $assetId,
                    'input' => $input,
                ],
            )
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new AuthOrgDataProvider('asset'),
            new OrgUserDataProvider('asset', [
                'assets-view',
                'assets-edit-nickname',
            ]),
            new ArrayDataProvider([
                'ok'                       => [
                    new GraphQLSuccess(
                        'asset',
                        new JsonFragment('setNickname', [
                            'result' => true,
                            'asset'  => [
                                'nickname' => 'new nickname',
                            ],
                        ]),
                    ),
                    static function (self $test, Organization $org): Asset {
                        return Asset::factory()->ownedBy($org)->create();
                    },
                    [
                        'nickname' => 'new nickname',
                    ],
                ],
                'reset nickname'           => [
                    new GraphQLSuccess(
                        'asset',
                        new JsonFragment('setNickname', [
                            'result' => true,
                            'asset'  => [
                                'nickname' => null,
                            ],
                        ]),
                    ),
                    static function (self $test, Organization $org): Asset {
                        return Asset::factory()->ownedBy($org)->create();
                    },
                    [
                        'nickname' => null,
                    ],
                ],
                'empty nickname'           => [
                    new GraphQLValidationError('asset', static function (): array {
                        return [
                            'input.nickname' => [
                                trans('validation.required'),
                            ],
                        ];
                    }),
                    static function (self $test, Organization $org): Asset {
                        return Asset::factory()->ownedBy($org)->create();
                    },
                    [
                        'nickname' => '',
                    ],
                ],
                'whitespace only nickname' => [
                    new GraphQLValidationError('asset', static function (): array {
                        return [
                            'input.nickname' => [
                                trans('validation.required'),
                            ],
                        ];
                    }),
                    static function (self $test, Organization $org): Asset {
                        return Asset::factory()->ownedBy($org)->create();
                    },
                    [
                        'nickname' => '    ',
                    ],
                ],
                'invalid asset'            => [
                    new GraphQLError('asset', static function (): Throwable {
                        return new ObjectNotFound((new Asset())->getMorphClass());
                    }),
                    static function (): Asset {
                        return Asset::factory()->make();
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
