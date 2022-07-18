<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Asset\ChangeRequest;

use App\GraphQL\Directives\Directives\Mutation\Exceptions\ObjectNotFound;
use App\Mail\RequestChange;
use App\Models\Asset;
use App\Models\Organization;
use App\Models\User;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
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

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Asset\ChangeRequest\Create
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class CreateTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory                          $orgFactory
     * @param UserFactory                                  $userFactory
     * @param array<string,mixed>                          $input
     * @param array<string,mixed>                          $settings
     * @param Closure(static, ?Organization, ?User): Asset $prepare
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        array $settings = null,
        Closure $prepare = null,
        array $input = null,
    ): void {
        // Prepare
        $org     = $this->setOrganization($orgFactory);
        $user    = $this->setUser($userFactory, $org);
        $assetId = $this->faker->uuid();

        $this->setSettings($settings);

        Mail::fake();

        if ($prepare) {
            $assetId = $prepare($this, $org, $user)->getKey();
        } elseif ($org) {
            $assetId = Asset::factory()->ownedBy($org)->create()->getKey();
        } else {
            // empty
        }

        $input ??= [
            'subject' => 'subject',
            'message' => 'change request',
        ];

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation test($id: ID!, $input: MessageInput!) {
                    asset(id: $id) {
                        changeRequest {
                            create(input: $input) {
                                result
                                changeRequest {
                                    subject
                                    message
                                    from
                                    to
                                    cc
                                    bcc
                                    user_id
                                    files {
                                        name
                                    }
                                }
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

        if ($expected instanceof GraphQLSuccess) {
            Mail::assertSent(RequestChange::class);
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        $prepare  = static function (TestCase $test, ?Organization $org, ?User $user): Asset {
            if ($user) {
                $user->email = 'user@example.com';
            }

            return Asset::factory()->ownedBy($org)->create();
        };
        $settings = [
            'ep.email_address' => 'test@example.com',
        ];

        return (new CompositeDataProvider(
            new AuthOrgDataProvider('asset', 'fd421bad-069f-491c-ad5f-5841aa9a9dff'),
            new OrgUserDataProvider(
                'asset',
                [
                    'requests-asset-change',
                ],
                'fd421bad-069f-491c-ad5f-5841aa9a9dee',
            ),
            new ArrayDataProvider([
                'ok'              => [
                    new GraphQLSuccess(
                        'asset',
                        new JsonFragment('changeRequest.create', [
                            'result'        => true,
                            'changeRequest' => [
                                'user_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dee',
                                'subject' => 'subject',
                                'message' => 'change request',
                                'from'    => 'user@example.com',
                                'to'      => ['test@example.com'],
                                'cc'      => ['cc@example.com'],
                                'bcc'     => ['bcc@example.com'],
                                'files'   => [
                                    [
                                        'name' => 'documents.csv',
                                    ],
                                ],
                            ],
                        ]),
                    ),
                    $settings,
                    $prepare,
                    [
                        'subject' => 'subject',
                        'message' => 'change request',
                        'cc'      => ['cc@example.com'],
                        'bcc'     => ['bcc@example.com'],
                        'files'   => [
                            UploadedFile::fake()->create('documents.csv', 100),
                        ],
                    ],
                ],
                'Invalid Object'  => [
                    new GraphQLError('asset', static function (): Throwable {
                        return new ObjectNotFound((new Asset())->getMorphClass());
                    }),
                    $settings,
                    static function (): Asset {
                        return Asset::factory()->make();
                    },
                    [
                        'subject' => 'subject',
                        'message' => 'change request',
                        'cc'      => ['cc@example.com'],
                        'bcc'     => ['bcc@example.com'],
                    ],
                ],
                'Invalid subject' => [
                    new GraphQLValidationError('asset'),
                    $settings,
                    $prepare,
                    [
                        'subject' => '',
                        'message' => 'change request',
                        'cc'      => ['cc@example.com'],
                        'bcc'     => ['bcc@example.com'],
                    ],
                ],
                'Invalid message' => [
                    new GraphQLValidationError('asset'),
                    $settings,
                    $prepare,
                    [
                        'subject' => 'subject',
                        'message' => '',
                        'cc'      => ['cc@example.com'],
                        'bcc'     => ['bcc@example.com'],
                    ],
                ],
                'Invalid cc'      => [
                    new GraphQLValidationError('asset'),
                    $settings,
                    $prepare,
                    [
                        'subject' => 'subject',
                        'message' => 'message',
                        'cc'      => ['wrong'],
                        'bcc'     => ['bcc@example.com'],
                    ],
                ],
                'Invalid bcc'     => [
                    new GraphQLValidationError('asset'),
                    $settings,
                    $prepare,
                    [
                        'subject' => 'subject',
                        'message' => 'message',
                        'cc'      => ['cc@example.com'],
                        'bcc'     => ['wrong'],
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
