<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Mail\QuoteRequest;
use App\Models\Asset;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Duration;
use App\Models\Oem;
use App\Models\Organization;
use App\Models\Reseller;
use App\Models\ServiceLevel;
use App\Models\Type;
use App\Models\User;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function __;
use function array_key_exists;
/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\CreateQuoteRequest
 */
class CreateQuoteRequestTest extends TestCase {
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
        array $input = [],
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
                $organization = $this->setOrganization(Organization::factory()->make());
            }

            if (!$settings) {
                $this->setSettings([
                    'ep.quote_types' => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ad'],
                ]);
            }

            $reseller = Reseller::factory()->create([
                'id' => $organization->getKey(),
            ]);
            Oem::factory()->create([
                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
            ]);
            $customer = Customer::factory()
                ->hasContacts(1, [
                    'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ac',
                ])
                ->create([
                    'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',

                ]);
            $customer->resellers()->attach($reseller);
            Type::factory()->create([
                'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                'object_type' => (new Document())->getMorphClass(),
            ]);
            Asset::factory()->create([
                'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                'reseller_id' => $reseller,
            ]);
            Duration::factory()->create([
                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
            ]);
            ServiceLevel::factory()->create([
                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ag',
            ]);
        }

        $map  = [];
        $file = [];

        if (array_key_exists('files', $input)) {
            if (!empty($input['files'])) {
                foreach ($input['files'] as $index => $item) {
                    $file[$index] = $item;
                    $map[$index]  = ["variables.input.files.{$index}"];
                }
                $input['files'] = null;
            }
        }

        $query      = /** @lang GraphQL */
            'mutation createQuoteRequest($input: CreateQuoteRequestInput!){
                createQuoteRequest(input: $input){
                    created {
                        oem_id
                        customer_id
                        contact_id
                        type_id
                        message
                        files {
                            name
                        }
                        assets {
                            id
                        }
                    }
                }
            }';
        $input      = $input ?: [
            'oem_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
            'customer_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
            'contact_id'  => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ac',
            'type_id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
            'assets'      => [
                [
                    'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                    'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                    'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ag',
                ],
            ],
            'message'     => null,
            'files'       => null,
        ];
        $operations = [
            'operationName' => 'createQuoteRequest',
            'query'         => $query,
            'variables'     => ['input' => $input],
        ];
        $this->multipartGraphQL($operations, $map, $file)->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            Mail::assertSent(QuoteRequest::class);
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        $prepare  = static function (TestCase $test, ?Organization $organization, User $user): void {
            if ($user) {
                $user->save();
            }
            $reseller = Reseller::factory()->create([
                'id' => $organization->getKey(),
            ]);
            Oem::factory()->create([
                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
            ]);
            $customer = Customer::factory()
                ->hasContacts(1, [
                    'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ac',
                ])
                ->create([
                    'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                ]);
            $customer->resellers()->attach($reseller);
            Type::factory()->create([
                'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                'object_type' => (new Document())->getMorphClass(),
            ]);
            Asset::factory()->create([
                'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                'reseller_id' => $reseller,
            ]);
            Duration::factory()->create([
                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
            ]);
            ServiceLevel::factory()->create([
                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ag',
            ]);
        };
        $input    = [
            'oem_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
            'customer_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
            'contact_id'  => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ac',
            'type_id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
            'assets'      => [
                [
                    'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                    'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                    'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ag',
                ],
            ],
            'message'     => 'message',
            'files'       => [UploadedFile::fake()->create('document.csv', 200)],
        ];
        $settings = [
            'ep.image.max_size' => 250,
            'ep.image.formats'  => ['csv'],
            'ep.quote_types'    => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ad'],
        ];
        return (new CompositeDataProvider(
            new OrganizationDataProvider('createQuoteRequest'),
            new UserDataProvider('createQuoteRequest', [
                'assets-view',
            ]),
            new ArrayDataProvider([
                'ok'                    => [
                    new GraphQLSuccess('createQuoteRequest', CreateQuoteRequest::class, [
                        'created' => [
                            'oem_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                            'customer_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                            'contact_id'  => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ac',
                            'type_id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                            'message'     => 'message',
                            'files'       => [
                                [
                                    'name' => 'document.csv',
                                ],
                            ],
                            'assets'      => [
                                [
                                    'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                ],
                            ],
                        ],
                    ]),
                    $settings,
                    $prepare,
                    $input,
                ],
                'Invalid oem'           => [
                    new GraphQLError('createQuoteRequest', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    [
                        'oem_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ba',
                        'customer_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'contact_id'  => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ac',
                        'type_id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'assets'      => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ag',
                            ],
                        ],
                    ],
                ],
                'Invalid customer'      => [
                    new GraphQLError('createQuoteRequest', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    [
                        'oem_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699bb',
                        'contact_id'  => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ac',
                        'type_id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'assets'      => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ag',
                            ],
                        ],
                    ],
                ],
                'Invalid contact'       => [
                    new GraphQLError('createQuoteRequest', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    [
                        'oem_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'contact_id'  => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699bc',
                        'type_id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'assets'      => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ag',
                            ],
                        ],
                    ],
                ],
                'Invalid type'          => [
                    new GraphQLError('createQuoteRequest', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    [
                        'oem_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'contact_id'  => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ac',
                        'type_id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699bd',
                        'assets'      => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ag',
                            ],
                        ],
                    ],
                ],
                'Invalid asset'         => [
                    new GraphQLError('createQuoteRequest', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    [
                        'oem_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'contact_id'  => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ac',
                        'type_id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'assets'      => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699be',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ag',
                            ],
                        ],
                    ],
                ],
                'Invalid duration'      => [
                    new GraphQLError('createQuoteRequest', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    [
                        'oem_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'contact_id'  => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ac',
                        'type_id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'assets'      => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699bf',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ag',
                            ],
                        ],
                    ],
                ],
                'Invalid service level' => [
                    new GraphQLError('createQuoteRequest', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    [
                        'oem_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'contact_id'  => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ac',
                        'type_id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'assets'      => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699bg',
                            ],
                        ],
                    ],
                ],
                'Invalid file size'     => [
                    new GraphQLError('createQuoteRequest', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    [
                        'oem_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'contact_id'  => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ac',
                        'type_id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'assets'      => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ag',
                            ],
                        ],
                        'message'     => 'message',
                        'files'       => [UploadedFile::fake()->create('document.csv', 300)],
                    ],
                ],
                'Invalid file format'   => [
                    new GraphQLError('createQuoteRequest', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    [
                        'oem_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'contact_id'  => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ac',
                        'type_id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'assets'      => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ag',
                            ],
                        ],
                        'message'     => 'message',
                        'files'       => [UploadedFile::fake()->create('document.jpg', 200)],
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
