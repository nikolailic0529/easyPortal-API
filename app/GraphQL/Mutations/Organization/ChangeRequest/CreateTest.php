<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Organization\ChangeRequest;

use App\GraphQL\Directives\Directives\Mutation\Exceptions\ObjectNotFound;
use App\Mail\RequestChange;
use App\Models\Organization;
use App\Models\User;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLValidationError;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithSettings;
use Tests\WithUser;
use Throwable;

use function trans;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\Organization\ChangeRequest\Create
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class CreateTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory                                      $orgFactory
     * @param UserFactory                                              $userFactory
     * @param SettingsFactory                                          $settings
     * @param Closure(static, ?Organization, ?User): Organization|null $prepare
     * @param array<string,mixed>|null                                 $input
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settings = null,
        Closure $prepare = null,
        array $input = null,
    ): void {
        // Prepare
        $org            = $this->setOrganization($orgFactory);
        $user           = $this->setUser($userFactory, $org);
        $organizationId = $this->faker->uuid();

        $this->setSettings($settings);

        Mail::fake();

        if ($prepare) {
            $organizationId = $prepare($this, $org, $user)->getKey();
        } elseif ($org) {
            $organizationId = Organization::factory()->create()->getKey();
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
                    organization(id: $id) {
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
                    'id'    => $organizationId,
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
        $prepare  = static function (TestCase $test, ?Organization $org, ?User $user): Organization {
            if ($user) {
                $user->email = 'user@example.com';
            }

            return Organization::factory()->create();
        };
        $settings = [
            'ep.email_address' => 'test@example.com',
        ];

        return (new CompositeDataProvider(
            new AuthOrgRootDataProvider('organization'),
            new OrgUserDataProvider(
                'organization',
                [
                    'administer',
                ],
                'ba3f651c-8183-421f-b1c9-1f1f7058e54d',
            ),
            new ArrayDataProvider([
                'ok'             => [
                    new GraphQLSuccess(
                        'organization',
                        new JsonFragment('changeRequest.create', [
                            'result'        => true,
                            'changeRequest' => [
                                'user_id' => 'ba3f651c-8183-421f-b1c9-1f1f7058e54d',
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
                'Invalid Object' => [
                    new GraphQLError('organization', static function (): Throwable {
                        return new ObjectNotFound((new Organization())->getMorphClass());
                    }),
                    $settings,
                    static function (): Organization {
                        return Organization::factory()->make();
                    },
                    [
                        'subject' => 'subject',
                        'message' => 'change request',
                        'cc'      => ['cc@example.com'],
                        'bcc'     => ['bcc@example.com'],
                    ],
                ],
                'Invalid input'  => [
                    new GraphQLValidationError('organization', static function (): array {
                        return [
                            'input.subject' => [
                                trans('validation.required'),
                            ],
                            'input.message' => [
                                trans('validation.required'),
                            ],
                            'input.cc.0'    => [
                                trans('validation.email'),
                            ],
                            'input.bcc.0'   => [
                                trans('validation.email'),
                            ],
                        ];
                    }),
                    $settings,
                    $prepare,
                    [
                        'subject' => '',
                        'message' => '',
                        'cc'      => ['wrong'],
                        'bcc'     => ['wrong'],
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
