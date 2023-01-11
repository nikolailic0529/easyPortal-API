<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Document\ChangeRequest;

use App\GraphQL\Directives\Directives\Mutation\Exceptions\ObjectNotFound;
use App\Mail\RequestChange;
use App\Models\Data\Type;
use App\Models\Document;
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

use function trans;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\Document\ChangeRequest\Create
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class CreateTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvokeContract
     *
     * @param OrganizationFactory                             $orgFactory
     * @param UserFactory                                     $userFactory
     * @param array<string,mixed>                             $input
     * @param array<string,mixed>                             $settings
     * @param Closure(static, ?Organization, ?User): Document $prepare
     */
    public function testInvokeContract(
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
        $typeId     = $settings['ep.contract_types'] ?? $this->faker->uuid();
        $documentId = $this->faker->uuid();

        $this->setSettings((array) $settings + ['ep.contract_types' => $typeId]);

        Mail::fake();

        if ($prepare) {
            $documentId = $prepare($this, $org, $user)->getKey();
        } elseif ($org) {
            $type       = Type::factory()->create(['id' => $typeId]);
            $documentId = Document::factory()
                ->ownedBy($org)
                ->create([
                    'type_id' => $type,
                ])
                ->getKey();
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
                    contract(id: $id) {
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
                    'id'    => $documentId,
                    'input' => $input,
                ],
            )
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            Mail::assertSent(RequestChange::class);
        }
    }

    /**
     * @dataProvider dataProviderInvokeQuote
     *
     * @param OrganizationFactory                             $orgFactory
     * @param UserFactory                                     $userFactory
     * @param array<string,mixed>                             $input
     * @param array<string,mixed>                             $settings
     * @param Closure(static, ?Organization, ?User): Document $prepare
     */
    public function testInvokeQuote(
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
        $typeId     = $settings['ep.quote_types'] ?? $this->faker->uuid();
        $documentId = $this->faker->uuid();

        $this->setSettings((array) $settings + ['ep.quote_types' => $typeId]);

        Mail::fake();

        if ($prepare) {
            $documentId = $prepare($this, $org, $user)->getKey();
        } elseif ($org) {
            $type       = Type::factory()->create(['id' => $typeId]);
            $documentId = Document::factory()
                ->ownedBy($org)
                ->create([
                    'type_id' => $type,
                ])
                ->getKey();
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
                    quote(id: $id) {
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
                    'id'    => $documentId,
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
    public function dataProviderInvokeContract(): array {
        $type     = '9ddfa0cb-307a-476b-b859-32ab4e0ad5b5';
        $prepare  = static function (TestCase $test, ?Organization $org, ?User $user) use ($type): Document {
            $type     = Type::factory()->create(['id' => $type]);
            $document = Document::factory()->ownedBy($org)->create([
                'type_id' => $type,
            ]);

            if ($user) {
                $user->email = 'user@example.com';
            }

            return $document;
        };
        $settings = [
            'ep.email_address'  => 'test@example.com',
            'ep.contract_types' => [$type],
        ];

        return (new CompositeDataProvider(
            new AuthOrgDataProvider('contract', 'fd421bad-069f-491c-ad5f-5841aa9a9dff'),
            new OrgUserDataProvider(
                'contract',
                [
                    'requests-contract-change',
                ],
                'fd421bad-069f-491c-ad5f-5841aa9a9dee',
            ),
            new ArrayDataProvider([
                'ok'             => [
                    new GraphQLSuccess(
                        'contract',
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
                    new GraphQLError('contract', static function (): Throwable {
                        return new ObjectNotFound((new Document())->getMorphClass());
                    }),
                    $settings,
                    static function (): Document {
                        return Document::factory()->make();
                    },
                    [
                        'subject' => 'subject',
                        'message' => 'change request',
                        'cc'      => ['cc@example.com'],
                        'bcc'     => ['bcc@example.com'],
                    ],
                ],
                'Invalid input'  => [
                    new GraphQLValidationError('contract', static function (): array {
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

    /**
     * @return array<mixed>
     */
    public function dataProviderInvokeQuote(): array {
        $type     = '9ddfa0cb-307a-476b-b859-32ab4e0ad5b5';
        $prepare  = static function (TestCase $test, ?Organization $org, ?User $user) use ($type): Document {
            $type     = Type::factory()->create(['id' => $type]);
            $document = Document::factory()->ownedBy($org)->create([
                'type_id' => $type,
            ]);

            if ($user) {
                $user->email = 'user@example.com';
            }

            return $document;
        };
        $settings = [
            'ep.email_address' => 'test@example.com',
            'ep.quote_types'   => [$type],
        ];

        return (new CompositeDataProvider(
            new AuthOrgDataProvider('quote', 'fd421bad-069f-491c-ad5f-5841aa9a9dff'),
            new OrgUserDataProvider(
                'quote',
                [
                    'requests-quote-change',
                ],
                'fd421bad-069f-491c-ad5f-5841aa9a9dee',
            ),
            new ArrayDataProvider([
                'ok'             => [
                    new GraphQLSuccess(
                        'quote',
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
                    new GraphQLError('quote', static function (): Throwable {
                        return new ObjectNotFound((new Document())->getMorphClass());
                    }),
                    $settings,
                    static function (): Document {
                        return Document::factory()->make();
                    },
                    [
                        'subject' => 'subject',
                        'message' => 'change request',
                        'cc'      => ['cc@example.com'],
                        'bcc'     => ['bcc@example.com'],
                    ],
                ],
                'Invalid input'  => [
                    new GraphQLValidationError('quote', static function (): array {
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
