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
use App\Models\ServiceGroup;
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
            $customer = Customer::factory()->create([
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
                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
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
                        type_id
                        message
                        oem {
                            id
                            key
                            name
                        }
                        customer {
                            id
                            name
                            assets_count
                            locations_count
                            locations {
                                id
                                state
                                postcode
                                line_one
                                line_two
                                latitude
                                longitude
                            }
                            contacts_count
                            contacts {
                                id
                                name
                                email
                                phone_number
                                phone_valid
                            }
                        }
                        contact {
                            name
                            email
                            phone_number
                            phone_valid
                        }
                        type {
                            id
                            name
                        }
                        files {
                            name
                        }
                        assets {
                            asset_id
                            service_level_id
                            serviceLevel {
                                id
                                name
                                description
                                sku
                                oem_id
                                service_group_id
                            }
                            duration_id
                            duration {
                                id
                                duration
                            }
                        }
                    }
                }
            }';
        $input      = $input ?: [
            'oem_id'        => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
            'customer_id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
            'type_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
            'contact_name'  => 'contact1',
            'contact_email' => 'contact1@test.com',
            'contact_phone' => '123219356',
            'assets'        => [
                [
                    'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                    'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                    'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
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
            $oem      = Oem::factory()->create([
                'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                'key'  => 'key1',
                'name' => 'oem1',
            ]);
            $customer = Customer::factory()->create([
                'id'              => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                'name'            => 'customer1',
                'assets_count'    => 0,
                'contacts_count'  => 0,
                'locations_count' => 0,
            ]);
            $customer->resellers()->attach($reseller);
            Type::factory()->create([
                'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                'name'        => 'new',
                'object_type' => (new Document())->getMorphClass(),
            ]);
            Asset::factory()->create([
                'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                'reseller_id' => $reseller,
            ]);
            Duration::factory()->create([
                'id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                'duration' => '5-10 years',
            ]);
            ServiceLevel::factory()
                ->for(ServiceGroup::factory()->state([
                    'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a5',
                ]))
                ->create([
                    'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
                    'oem_id'      => $oem,
                    'sku'         => 'SKU#123',
                    'name'        => 'Level',
                    'description' => 'description',
                ]);
        };
        $input    = [
            'oem_id'        => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
            'customer_id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
            'type_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
            'contact_name'  => 'contact1',
            'contact_email' => 'contact1@test.com',
            'contact_phone' => '123219356',
            'assets'        => [
                [
                    'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                    'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                    'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
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
                            'type_id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                            'message'     => 'message',
                            'oem'         => [
                                'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                                'key'  => 'key1',
                                'name' => 'oem1',
                            ],
                            'customer'    => [
                                'id'              => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                                'name'            => 'customer1',
                                'assets_count'    => 0,
                                'contacts_count'  => 0,
                                'locations_count' => 0,
                                'contacts'        => [],
                                'locations'       => [],
                            ],
                            'contact'     => [
                                'email'        => 'contact1@test.com',
                                'name'         => 'contact1',
                                'phone_number' => '123219356',
                                'phone_valid'  => false,
                            ],
                            'type'        => [
                                'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                                'name' => 'new',
                            ],
                            'files'       => [
                                [
                                    'name' => 'document.csv',
                                ],
                            ],
                            'assets'      => [
                                [
                                    'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                    'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                    'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
                                    'duration'         => [
                                        'id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                        'duration' => '5-10 years',
                                    ],
                                    'serviceLevel'     => [
                                        'id'               => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
                                        'oem_id'           => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                                        'service_group_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a5',
                                        'sku'              => 'SKU#123',
                                        'name'             => 'Level',
                                        'description'      => 'description',
                                    ],
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
                        'oem_id'        => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ba',
                        'customer_id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'type_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'  => 'contact1',
                        'contact_email' => 'contact1@test.com',
                        'contact_phone' => '123219356',
                        'assets'        => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
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
                        'oem_id'        => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699bb',
                        'type_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'  => 'contact1',
                        'contact_email' => 'contact1@test.com',
                        'contact_phone' => '123219356',
                        'assets'        => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
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
                        'oem_id'        => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'type_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'  => '',
                        'contact_email' => '',
                        'contact_phone' => '',
                        'assets'        => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
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
                        'oem_id'        => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'type_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699bd',
                        'contact_name'  => 'contact1',
                        'contact_email' => 'contact1@test.com',
                        'contact_phone' => '123219356',
                        'assets'        => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
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
                        'oem_id'        => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'type_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'  => 'contact1',
                        'contact_email' => 'contact1@test.com',
                        'contact_phone' => '123219356',
                        'assets'        => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699be',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
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
                        'oem_id'        => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'type_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'  => 'contact1',
                        'contact_email' => 'contact1@test.com',
                        'contact_phone' => '123219356',
                        'assets'        => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699bf',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
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
                        'oem_id'        => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'type_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'  => 'contact1',
                        'contact_email' => 'contact1@test.com',
                        'contact_phone' => '123219356',
                        'assets'        => [
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
                        'oem_id'        => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'type_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'  => 'contact1',
                        'contact_email' => 'contact1@test.com',
                        'contact_phone' => '123219356',
                        'assets'        => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
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
                        'oem_id'        => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'type_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'  => 'contact1',
                        'contact_email' => 'contact1@test.com',
                        'contact_phone' => '123219356',
                        'assets'        => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
                            ],
                        ],
                        'message'       => 'message',
                        'files'         => [UploadedFile::fake()->create('document.jpg', 200)],
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
