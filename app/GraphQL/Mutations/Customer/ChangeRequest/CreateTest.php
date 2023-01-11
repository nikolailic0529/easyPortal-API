<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Customer\ChangeRequest;

use App\GraphQL\Directives\Directives\Mutation\Exceptions\ObjectNotFound;
use App\Mail\RequestChange;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\User;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgResellerDataProvider;
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
 * @covers \App\GraphQL\Mutations\Customer\ChangeRequest\Create
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class CreateTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory                             $orgFactory
     * @param UserFactory                                     $userFactory
     * @param array<string,mixed>                             $input
     * @param array<string,mixed>                             $settings
     * @param Closure(static, ?Organization, ?User): Customer $prepare
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
        $org        = $this->setOrganization($orgFactory);
        $user       = $this->setUser($userFactory, $org);
        $customerId = $this->faker->uuid();

        $this->setSettings($settings);

        Mail::fake();

        if ($prepare) {
            $customerId = $prepare($this, $org, $user)->getKey();
        } elseif ($org) {
            $customerId = Customer::factory()->ownedBy($org)->create()->getKey();
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
                    customer(id: $id) {
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
                    'id'    => $customerId,
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
        $prepare  = static function (TestCase $test, ?Organization $org, ?User $user): Customer {
            if ($user) {
                $user->email = 'user@example.com';
            }

            return Customer::factory()->ownedBy($org)->create();
        };
        $settings = [
            'ep.email_address' => 'test@example.com',
        ];

        return (new CompositeDataProvider(
            new AuthOrgResellerDataProvider('customer'),
            new OrgUserDataProvider(
                'customer',
                [
                    'requests-customer-change',
                ],
                'fd421bad-069f-491c-ad5f-5841aa9a9dee',
            ),
            new ArrayDataProvider([
                'ok'             => [
                    new GraphQLSuccess(
                        'customer',
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
                'Invalid Object' => [
                    new GraphQLError('customer', static function (): Throwable {
                        return new ObjectNotFound((new Customer())->getMorphClass());
                    }),
                    $settings,
                    static function (): Customer {
                        return Customer::factory()->make();
                    },
                    [
                        'subject' => 'subject',
                        'message' => 'change request',
                        'cc'      => ['cc@example.com'],
                        'bcc'     => ['bcc@example.com'],
                    ],
                ],
                'Invalid input'  => [
                    new GraphQLValidationError('customer', static function (): array {
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
