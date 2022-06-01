<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Mail\RequestChange;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Reseller;
use App\Models\Type;
use App\Models\User;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function __;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\RequestQuoteChange
 */
class RequestQuoteChangeTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     *
     * @param array<string,mixed> $input
     *
     * @param array<string,mixed> $settings
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        array $settings = null,
        Closure $prepare = null,
        array $input = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);
        $this->setSettings($settings);

        Mail::fake();

        if ($prepare) {
            $prepare($this, $organization, $user);
        } else {
            // Lighthouse performs validation BEFORE permission check :(
            //
            // https://github.com/nuwave/lighthouse/issues/1780
            //
            // Following code required to "fix" it
            if (!$organization) {
                $organization = $this->setOrganization(Organization::factory()->create());
            }

            if (!$settings) {
                $this->setSettings([
                    'ep.document_statuses_hidden' => [],
                    'ep.quote_types'              => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ac'],
                ]);
            }

            $type     = Type::factory()->create([
                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ac',
            ]);
            $reseller = Reseller::factory()->create([
                'id' => $organization->getKey(),
            ]);

            Document::factory()->create([
                'reseller_id' => $reseller->getKey(),
                'type_id'     => $type->getKey(),
                'id'          => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
            ]);
        }

        $input = $input ?: [
                'from'     => 'user@example.com',
                'subject'  => 'subject',
                'message'  => 'message',
                'quote_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
            ];
        $map   = [];
        $file  = [];

        if (isset($input['files'])) {
            foreach ((array) $input['files'] as $index => $item) {
                $file[$index] = $item;
                $map[$index]  = ["variables.input.files.{$index}"];
            }

            $input['files'] = null;
        }

        $query      = /** @lang GraphQL */ 'mutation RequestQuoteChange($input: RequestQuoteChangeInput!) {
            requestQuoteChange(input:$input) {
                created {
                    subject
                    message
                    from
                    to
                    cc
                    bcc
                    user_id
                    user {
                        id
                        given_name
                        family_name
                    }
                    files {
                        name
                    }
                }
            }
        }';
        $operations = [
            'operationName' => 'RequestQuoteChange',
            'query'         => $query,
            'variables'     => ['input' => $input],
        ];
        // Test
        $this->multipartGraphQL($operations, $map, $file)->assertThat($expected);

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
        $prepare  = static function (TestCase $test, ?Organization $organization): void {
            $reseller = Reseller::factory()->create([
                'id' => $organization->getKey(),
            ]);
            $type     = Type::factory()->create([
                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ac',
            ]);
            Document::factory()->create([
                'type_id'     => $type->getKey(),
                'reseller_id' => $reseller->getKey(),
                'id'          => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
            ]);
        };
        $settings = [
            'ep.email_address'            => 'test@example.com',
            'ep.quote_types'              => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ac'],
            'ep.document_statuses_hidden' => [],
        ];

        return (new CompositeDataProvider(
            new OrganizationDataProvider('requestQuoteChange'),
            new OrganizationUserDataProvider(
                'requestQuoteChange',
                [
                    'requests-quote-change',
                ],
                static function (User $user): void {
                    $user->id          = 'fd421bad-069f-491c-ad5f-5841aa9a9dee';
                    $user->given_name  = 'first';
                    $user->family_name = 'last';
                },
            ),
            new ArrayDataProvider([
                'ok'              => [
                    new GraphQLSuccess('requestQuoteChange', RequestAssetChange::class, [
                        'created' => [
                            'user_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dee',
                            'subject' => 'subject',
                            'message' => 'change request',
                            'from'    => 'user@example.com',
                            'to'      => ['test@example.com'],
                            'cc'      => ['cc@example.com'],
                            'bcc'     => ['bcc@example.com'],
                            'user'    => [
                                'id'          => 'fd421bad-069f-491c-ad5f-5841aa9a9dee',
                                'given_name'  => 'first',
                                'family_name' => 'last',
                            ],
                            'files'   => [
                                [
                                    'name' => 'documents.csv',
                                ],
                            ],
                        ],
                    ]),
                    $settings,
                    $prepare,
                    [
                        'quote_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                        'subject'  => 'subject',
                        'message'  => 'change request',
                        'from'     => 'user@example.com',
                        'cc'       => ['cc@example.com'],
                        'bcc'      => ['bcc@example.com'],
                        'files'    => [
                            UploadedFile::fake()->create('documents.csv', 100),
                        ],
                    ],
                ],
                'Invalid Quote'   => [
                    new GraphQLError('requestQuoteChange', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    [
                        'quote_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                        'subject'  => 'subject',
                        'message'  => 'change request',
                        'from'     => 'user@example.com',
                        'cc'       => ['cc@example.com'],
                        'bcc'      => ['bcc@example.com'],
                    ],
                ],
                'Invalid subject' => [
                    new GraphQLError('requestQuoteChange', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    [
                        'subject'  => '',
                        'message'  => 'change request',
                        'from'     => 'user@example.com',
                        'cc'       => ['cc@example.com'],
                        'bcc'      => ['bcc@example.com'],
                        'quote_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                    ],
                ],
                'Invalid message' => [
                    new GraphQLError('requestQuoteChange', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    [
                        'subject'  => 'subject',
                        'message'  => '',
                        'from'     => 'user@example.com',
                        'cc'       => ['cc@example.com'],
                        'bcc'      => ['bcc@example.com'],
                        'quote_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                    ],
                ],
                'Invalid cc'      => [
                    new GraphQLError('requestQuoteChange', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    [
                        'subject'  => 'subject',
                        'message'  => 'message',
                        'from'     => 'user@example.com',
                        'cc'       => ['wrong'],
                        'bcc'      => ['bcc@example.com'],
                        'quote_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                    ],
                ],
                'Invalid bcc'     => [
                    new GraphQLError('requestQuoteChange', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    [
                        'subject'  => 'subject',
                        'message'  => 'message',
                        'from'     => 'user@example.com',
                        'cc'       => ['cc@example.com'],
                        'bcc'      => ['wrong'],
                        'quote_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
