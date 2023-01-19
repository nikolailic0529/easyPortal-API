<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Document\ChangeRequest;

use App\GraphQL\Directives\Directives\Mutation\Exceptions\ObjectNotFound;
use App\Mail\RequestChange;
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
use Tests\WithSettings;
use Tests\WithUser;
use Throwable;

use function trans;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\Document\ChangeRequest\Create
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class CreateTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvokeContract
     *
     * @param OrganizationFactory                                  $orgFactory
     * @param UserFactory                                          $userFactory
     * @param SettingsFactory                                      $settings
     * @param Closure(static, ?Organization, ?User): Document|null $prepare
     * @param array<string,mixed>|null                             $input
     */
    public function testInvokeContract(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settings = null,
        Closure $prepare = null,
        array $input = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);
        $key  = $this->faker->uuid();

        $this->setSettings($settings);

        Mail::fake();

        if ($prepare) {
            $key = $prepare($this, $org, $user)->getKey();
        } elseif ($org) {
            $key = Document::factory()
                ->ownedBy($org)
                ->create([
                    'is_hidden'   => false,
                    'is_contract' => true,
                    'is_quote'    => false,
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
                    'id'    => $key,
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
     * @param OrganizationFactory                                  $orgFactory
     * @param UserFactory                                          $userFactory
     * @param SettingsFactory                                      $settings
     * @param Closure(static, ?Organization, ?User): Document|null $prepare
     * @param array<string,mixed>|null                             $input
     */
    public function testInvokeQuote(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settings = null,
        Closure $prepare = null,
        array $input = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);
        $key  = $this->faker->uuid();

        $this->setSettings($settings);

        Mail::fake();

        if ($prepare) {
            $key = $prepare($this, $org, $user)->getKey();
        } elseif ($org) {
            $key = Document::factory()
                ->ownedBy($org)
                ->create([
                    'is_hidden'   => false,
                    'is_contract' => false,
                    'is_quote'    => true,
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
                    'id'    => $key,
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
        $prepare  = static function (TestCase $test, ?Organization $org, ?User $user): Document {
            $document = Document::factory()->ownedBy($org)->create([
                'is_hidden'   => false,
                'is_contract' => true,
                'is_quote'    => false,
            ]);

            if ($user) {
                $user->email = 'user@example.com';
            }

            return $document;
        };
        $settings = [
            'ep.email_address' => 'test@example.com',
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
        $prepare  = static function (TestCase $test, ?Organization $org, ?User $user): Document {
            $document = Document::factory()->ownedBy($org)->create([
                'is_hidden'   => false,
                'is_contract' => false,
                'is_quote'    => true,
            ]);

            if ($user) {
                $user->email = 'user@example.com';
            }

            return $document;
        };
        $settings = [
            'ep.email_address' => 'test@example.com',
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
