<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\QuoteRequest;

use App\Mail\QuoteRequest;
use App\Models\Asset;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Oem;
use App\Models\Organization;
use App\Models\QuoteRequestDuration;
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
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLValidationError;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithSettings;
use Tests\WithUser;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\QuoteRequest\Create
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class CreateTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     * @param array<string,mixed> $input
     * @param SettingsFactory     $settingsFactory
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $prepare = null,
        array $input = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        $this->setSettings($settingsFactory);

        Mail::fake();

        if ($prepare) {
            $prepare($this, $org, $user);
        } elseif ($org) {
            $input ??= [
                'oem_id'        => Oem::factory()->create()->getKey(),
                'customer_id'   => Customer::factory()->create()->getKey(),
                'type_id'       => Type::factory()->create()->getKey(),
                'contact_name'  => $this->faker->name(),
                'contact_email' => $this->faker->email(),
                'contact_phone' => $this->faker->e164PhoneNumber(),
            ];
        } else {
            $input ??= [
                'oem_id'        => $this->faker->uuid(),
                'customer_id'   => $this->faker->uuid(),
                'type_id'       => $this->faker->uuid(),
                'contact_name'  => $this->faker->name(),
                'contact_email' => $this->faker->email(),
                'contact_phone' => $this->faker->e164PhoneNumber(),
            ];
        }

        $map  = [];
        $file = [];

        if (isset($input['files'])) {
            foreach ((array) $input['files'] as $index => $item) {
                $file[$index] = $item;
                $map[$index]  = ["variables.input.files.{$index}"];
            }

            $input['files'] = null;
        }

        $query      = /** @lang GraphQL */
            <<<'GRAPHQL'
            mutation test($input: QuoteRequestCreateInput!) {
                quoteRequest {
                    create(input: $input) {
                        result
                        quoteRequest {
                            customer_id
                            customer_custom
                            oem_id
                            oem_custom
                            type_id
                            type_custom
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
                                    location_id
                                    location {
                                        id
                                        state
                                        postcode
                                        line_one
                                        line_two
                                        latitude
                                        longitude
                                    }
                                }
                                contacts_count
                                contacts {
                                    id
                                    name
                                    email
                                    phone_number
                                    phone_valid
                                }
                                changed_at
                                synced_at
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
                                service_level_custom
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
                                    name
                                    key
                                }
                            }
                            documents {
                                document_id
                                document {
                                    id
                                }
                                duration_id
                                duration {
                                    id
                                    name
                                    key
                                }
                            }
                        }
                    }
                }
            }
        GRAPHQL;
        $operations = [
            'operationName' => 'test',
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
        $type     = 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad';
        $prepare  = static function (TestCase $test, ?Organization $organization, ?User $user) use ($type): void {
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
                'changed_at'      => '2021-10-19 10:15:00',
                'synced_at'       => '2021-10-19 10:25:00',
            ]);
            $customer->resellers()->attach($reseller);
            $type = Type::factory()->create([
                'id'          => $type,
                'name'        => 'new',
                'object_type' => (new Document())->getMorphClass(),
            ]);
            Document::factory()->create([
                'id'          => '047f06f5-e62f-464a-8df8-bd9834e21915',
                'type_id'     => $type,
                'reseller_id' => $reseller,
            ]);
            Asset::factory()->create([
                'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                'reseller_id' => $reseller,
            ]);
            QuoteRequestDuration::factory()->create([
                'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                'name' => '5-10 years',
                'key'  => '5-10 years',
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
        $settings = [
            'ep.file.max_size'            => 250,
            'ep.file.formats'             => ['csv'],
            'ep.quote_types'              => [$type],
            'ep.document_statuses_hidden' => [],
        ];

        return (new CompositeDataProvider(
            new AuthOrgDataProvider('quoteRequest'),
            new OrgUserDataProvider('quoteRequest', [
                'requests-quote-add',
            ]),
            new ArrayDataProvider([
                'ok'                                        => [
                    new GraphQLSuccess(
                        'quoteRequest',
                        new JsonFragment('create', [
                            'result'       => true,
                            'quoteRequest' => [
                                'oem_custom'      => null,
                                'oem_id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                                'customer_id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                                'customer_custom' => null,
                                'type_custom'     => null,
                                'type_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                                'message'         => 'message',
                                'oem'             => [
                                    'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                                    'key'  => 'key1',
                                    'name' => 'oem1',
                                ],
                                'customer'        => [
                                    'id'              => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                                    'name'            => 'customer1',
                                    'assets_count'    => 0,
                                    'contacts_count'  => 0,
                                    'locations_count' => 0,
                                    'contacts'        => [],
                                    'locations'       => [],
                                    'changed_at'      => '2021-10-19T10:15:00+00:00',
                                    'synced_at'       => '2021-10-19T10:25:00+00:00',
                                ],
                                'contact'         => [
                                    'email'        => 'contact1@test.com',
                                    'name'         => 'contact1',
                                    'phone_number' => '+27113456789',
                                    'phone_valid'  => true,
                                ],
                                'type'            => [
                                    'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                                    'name' => 'new',
                                ],
                                'files'           => [
                                    [
                                        'name' => 'document.csv',
                                    ],
                                ],
                                'assets'          => [
                                    [
                                        'asset_id'             => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                        'duration_id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                        'service_level_custom' => null,
                                        'service_level_id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
                                        'duration'             => [
                                            'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                            'name' => '5-10 years',
                                            'key'  => '5-10 years',
                                        ],
                                        'serviceLevel'         => [
                                            'id'               => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
                                            'oem_id'           => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                                            'service_group_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a5',
                                            'sku'              => 'SKU#123',
                                            'name'             => 'Level',
                                            'description'      => 'description',
                                        ],
                                    ],
                                ],
                                'documents'       => [
                                    [
                                        'document_id' => '047f06f5-e62f-464a-8df8-bd9834e21915',
                                        'document'    => [
                                            'id' => '047f06f5-e62f-464a-8df8-bd9834e21915',
                                        ],
                                        'duration_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                        'duration'    => [
                                            'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                            'name' => '5-10 years',
                                            'key'  => '5-10 years',
                                        ],
                                    ],
                                ],
                            ],
                        ]),
                    ),
                    $settings,
                    $prepare,
                    [
                        'oem_id'        => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'type_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'  => 'contact1',
                        'contact_email' => 'contact1@test.com',
                        'contact_phone' => '+27113456789',
                        'assets'        => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
                            ],
                        ],
                        'documents'     => [
                            [
                                'document_id' => '047f06f5-e62f-464a-8df8-bd9834e21915',
                                'duration_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                            ],
                        ],
                        'message'       => 'message',
                        'files'         => [UploadedFile::fake()->create('document.csv', 200)],
                    ],
                ],
                'ok (custom)'                               => [
                    new GraphQLSuccess(
                        'quoteRequest',
                        new JsonFragment('create', [
                            'result'       => true,
                            'quoteRequest' => [
                                'message'         => null,
                                'oem_custom'      => 'Custom OEM',
                                'oem_id'          => null,
                                'oem'             => null,
                                'customer_custom' => 'Custom Customer',
                                'customer_id'     => null,
                                'customer'        => null,
                                'contact'         => [
                                    'email'        => 'contact1@test.com',
                                    'name'         => 'contact1',
                                    'phone_number' => '+27113456789',
                                    'phone_valid'  => true,
                                ],
                                'type_custom'     => 'Custom Type',
                                'type_id'         => null,
                                'type'            => null,
                                'files'           => [
                                    // empty
                                ],
                                'assets'          => [
                                    [
                                        'asset_id'             => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                        'duration_id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                        'duration'             => [
                                            'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                            'name' => '5-10 years',
                                            'key'  => '5-10 years',
                                        ],
                                        'service_level_custom' => 'Custom ServiceLevel',
                                        'service_level_id'     => null,
                                        'serviceLevel'         => null,
                                    ],
                                ],
                                'documents'       => [
                                    // empty
                                ],
                            ],
                        ]),
                    ),
                    $settings,
                    $prepare,
                    [
                        'oem_custom'      => 'Custom OEM',
                        'type_custom'     => 'Custom Type',
                        'customer_custom' => 'Custom Customer',
                        'contact_name'    => 'contact1',
                        'contact_email'   => 'contact1@test.com',
                        'contact_phone'   => '+27113456789',
                        'assets'          => [
                            [
                                'asset_id'             => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_custom' => 'Custom ServiceLevel',
                            ],
                        ],
                    ],
                ],
                'ok-customer_id null customer_custom'       => [
                    new GraphQLSuccess(
                        'quoteRequest',
                        new JsonFragment('create', [
                            'result'       => true,
                            'quoteRequest' => [
                                'oem_custom'      => null,
                                'oem_id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                                'customer_id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                                'customer_custom' => null,
                                'type_custom'     => null,
                                'type_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                                'message'         => 'message',
                                'oem'             => [
                                    'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                                    'key'  => 'key1',
                                    'name' => 'oem1',
                                ],
                                'customer'        => [
                                    'id'              => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                                    'name'            => 'customer1',
                                    'assets_count'    => 0,
                                    'contacts_count'  => 0,
                                    'locations_count' => 0,
                                    'contacts'        => [],
                                    'locations'       => [],
                                    'changed_at'      => '2021-10-19T10:15:00+00:00',
                                    'synced_at'       => '2021-10-19T10:25:00+00:00',
                                ],
                                'contact'         => [
                                    'email'        => 'contact1@test.com',
                                    'name'         => 'contact1',
                                    'phone_number' => '+27113456789',
                                    'phone_valid'  => true,
                                ],
                                'type'            => [
                                    'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                                    'name' => 'new',
                                ],
                                'files'           => [
                                    [
                                        'name' => 'document.csv',
                                    ],
                                ],
                                'assets'          => [
                                    [
                                        'asset_id'             => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                        'duration_id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                        'service_level_custom' => null,
                                        'service_level_id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
                                        'duration'             => [
                                            'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                            'name' => '5-10 years',
                                            'key'  => '5-10 years',
                                        ],
                                        'serviceLevel'         => [
                                            'id'               => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
                                            'oem_id'           => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                                            'service_group_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a5',
                                            'sku'              => 'SKU#123',
                                            'name'             => 'Level',
                                            'description'      => 'description',
                                        ],
                                    ],
                                ],
                                'documents'       => [
                                    // empty
                                ],
                            ],
                        ]),
                    ),
                    $settings,
                    $prepare,
                    [
                        'oem_id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'customer_custom' => null,
                        'type_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'    => 'contact1',
                        'contact_email'   => 'contact1@test.com',
                        'contact_phone'   => '+27113456789',
                        'assets'          => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
                            ],
                        ],
                        'message'         => 'message',
                        'files'           => [UploadedFile::fake()->create('document.csv', 200)],
                    ],
                ],
                'ok-customer_custom empty customer_id'      => [
                    new GraphQLSuccess(
                        'quoteRequest',
                        new JsonFragment('create', [
                            'result'       => true,
                            'quoteRequest' => [
                                'oem_custom'      => null,
                                'oem_id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                                'customer_id'     => null,
                                'customer_custom' => 'name',
                                'type_custom'     => null,
                                'type_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                                'message'         => 'message',
                                'oem'             => [
                                    'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                                    'key'  => 'key1',
                                    'name' => 'oem1',
                                ],
                                'customer'        => null,
                                'contact'         => [
                                    'email'        => 'contact1@test.com',
                                    'name'         => 'contact1',
                                    'phone_number' => '+27113456789',
                                    'phone_valid'  => true,
                                ],
                                'type'            => [
                                    'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                                    'name' => 'new',
                                ],
                                'files'           => [
                                    [
                                        'name' => 'document.csv',
                                    ],
                                ],
                                'assets'          => [
                                    [
                                        'asset_id'             => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                        'duration_id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                        'service_level_custom' => null,
                                        'service_level_id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
                                        'duration'             => [
                                            'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                            'name' => '5-10 years',
                                            'key'  => '5-10 years',
                                        ],
                                        'serviceLevel'         => [
                                            'id'               => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
                                            'oem_id'           => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                                            'service_group_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a5',
                                            'sku'              => 'SKU#123',
                                            'name'             => 'Level',
                                            'description'      => 'description',
                                        ],
                                    ],
                                ],
                                'documents'       => [
                                    // empty
                                ],
                            ],
                        ]),
                    ),
                    $settings,
                    $prepare,
                    [
                        'oem_id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_custom' => 'name',
                        'type_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'    => 'contact1',
                        'contact_email'   => 'contact1@test.com',
                        'contact_phone'   => '+27113456789',
                        'assets'          => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
                            ],
                        ],
                        'message'         => 'message',
                        'files'           => [UploadedFile::fake()->create('document.csv', 200)],
                    ],
                ],
                'ok-customer_custom null customer_id'       => [
                    new GraphQLSuccess(
                        'quoteRequest',
                        new JsonFragment('create', [
                            'result'       => true,
                            'quoteRequest' => [
                                'oem_custom'      => null,
                                'oem_id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                                'customer_id'     => null,
                                'customer_custom' => 'name',
                                'type_custom'     => null,
                                'type_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                                'message'         => 'message',
                                'oem'             => [
                                    'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                                    'key'  => 'key1',
                                    'name' => 'oem1',
                                ],
                                'customer'        => null,
                                'contact'         => [
                                    'email'        => 'contact1@test.com',
                                    'name'         => 'contact1',
                                    'phone_number' => '+27113456789',
                                    'phone_valid'  => true,
                                ],
                                'type'            => [
                                    'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                                    'name' => 'new',
                                ],
                                'files'           => [
                                    [
                                        'name' => 'document.csv',
                                    ],
                                ],
                                'assets'          => [
                                    [
                                        'asset_id'             => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                        'duration_id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                        'service_level_custom' => null,
                                        'service_level_id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
                                        'duration'             => [
                                            'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                            'name' => '5-10 years',
                                            'key'  => '5-10 years',
                                        ],
                                        'serviceLevel'         => [
                                            'id'               => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
                                            'oem_id'           => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                                            'service_group_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a5',
                                            'sku'              => 'SKU#123',
                                            'name'             => 'Level',
                                            'description'      => 'description',
                                        ],
                                    ],
                                ],
                                'documents'       => [
                                    // empty
                                ],
                            ],
                        ]),
                    ),
                    $settings,
                    $prepare,
                    [
                        'oem_id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id'     => null,
                        'customer_custom' => 'name',
                        'type_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'    => 'contact1',
                        'contact_email'   => 'contact1@test.com',
                        'contact_phone'   => '+27113456789',
                        'assets'          => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
                            ],
                        ],
                        'message'         => 'message',
                        'files'           => [UploadedFile::fake()->create('document.csv', 200)],
                    ],
                ],
                'ok: assets null'                           => [
                    new GraphQLSuccess(
                        'quoteRequest',
                        new JsonFragment('create', [
                            'result'       => true,
                            'quoteRequest' => [
                                'oem_custom'      => null,
                                'oem_id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                                'customer_id'     => null,
                                'customer_custom' => 'name',
                                'type_custom'     => null,
                                'type_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                                'message'         => 'message',
                                'oem'             => [
                                    'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                                    'key'  => 'key1',
                                    'name' => 'oem1',
                                ],
                                'customer'        => null,
                                'contact'         => [
                                    'email'        => 'contact1@test.com',
                                    'name'         => 'contact1',
                                    'phone_number' => '+27113456789',
                                    'phone_valid'  => true,
                                ],
                                'type'            => [
                                    'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                                    'name' => 'new',
                                ],
                                'files'           => [
                                    [
                                        'name' => 'document.csv',
                                    ],
                                ],
                                'assets'          => [
                                    // empty
                                ],
                                'documents'       => [
                                    // empty
                                ],
                            ],
                        ]),
                    ),
                    $settings,
                    $prepare,
                    [
                        'oem_id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_custom' => 'name',
                        'type_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'    => 'contact1',
                        'contact_email'   => 'contact1@test.com',
                        'contact_phone'   => '+27113456789',
                        'assets'          => null,
                        'message'         => 'message',
                        'files'           => [UploadedFile::fake()->create('document.csv', 200)],
                    ],
                ],
                'Invalid oem'                               => [
                    new GraphQLValidationError('quoteRequest'),
                    $settings,
                    $prepare,
                    [
                        'oem_id'        => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ax',
                        'customer_id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'type_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'  => 'contact1',
                        'contact_email' => 'contact1@test.com',
                        'contact_phone' => '+27113456789',
                        'assets'        => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
                            ],
                        ],
                    ],
                ],
                'Invalid customer id'                       => [
                    new GraphQLValidationError('quoteRequest'),
                    $settings,
                    $prepare,
                    [
                        'oem_id'        => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ax',
                        'type_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'  => 'contact1',
                        'contact_email' => 'contact1@test.com',
                        'contact_phone' => '+27113456789',
                        'assets'        => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
                            ],
                        ],
                    ],
                ],
                'Invalid contact name'                      => [
                    new GraphQLValidationError('quoteRequest'),
                    $settings,
                    $prepare,
                    [
                        'oem_id'        => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'type_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'  => '',
                        'contact_email' => 'contact1@test.com',
                        'contact_phone' => '+27113456789',
                        'assets'        => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
                            ],
                        ],
                    ],
                ],
                'Invalid contact phone'                     => [
                    new GraphQLValidationError('quoteRequest'),
                    $settings,
                    $prepare,
                    [
                        'oem_id'        => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'type_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'  => 'contact1',
                        'contact_email' => 'contact1@test.com',
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
                'Invalid contact email'                     => [
                    new GraphQLValidationError('quoteRequest'),
                    $settings,
                    $prepare,
                    [
                        'oem_id'        => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'type_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'  => 'contact1',
                        'contact_email' => 'invalid email',
                        'contact_phone' => '+27113456789',
                    ],
                ],
                'Invalid type'                              => [
                    new GraphQLValidationError('quoteRequest'),
                    $settings,
                    $prepare,
                    [
                        'oem_id'        => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'type_id'       => '',
                        'contact_name'  => 'contact1',
                        'contact_email' => 'contact1@test.com',
                        'contact_phone' => '+27113456789',
                    ],
                ],
                'Invalid asset'                             => [
                    new GraphQLValidationError('quoteRequest'),
                    $settings,
                    $prepare,
                    [
                        'oem_id'        => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'type_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'  => 'contact1',
                        'contact_email' => 'contact1@test.com',
                        'contact_phone' => '+27113456789',
                        'assets'        => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699be',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
                            ],
                        ],
                    ],
                ],
                'Invalid duration'                          => [
                    new GraphQLValidationError('quoteRequest'),
                    $settings,
                    $prepare,
                    [
                        'oem_id'        => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'type_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'  => 'contact1',
                        'contact_email' => 'contact1@test.com',
                        'contact_phone' => '+27113456789',
                        'assets'        => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ax',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
                            ],
                        ],
                    ],
                ],
                'Invalid service level'                     => [
                    new GraphQLValidationError('quoteRequest'),
                    $settings,
                    $prepare,
                    [
                        'oem_id'        => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'type_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'  => 'contact1',
                        'contact_email' => 'contact1@test.com',
                        'contact_phone' => '+27113456789',
                        'assets'        => [
                            [
                                'asset_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ax',
                            ],
                        ],
                    ],
                ],
                'Invalid file size'                         => [
                    new GraphQLValidationError('quoteRequest'),
                    $settings,
                    $prepare,
                    [
                        'oem_id'        => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'type_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'  => 'contact1',
                        'contact_email' => 'contact1@test.com',
                        'contact_phone' => '+27113456789',
                        'message'       => 'message',
                        'files'         => [UploadedFile::fake()->create('document.csv', 300)],
                    ],
                ],
                'Invalid file format'                       => [
                    new GraphQLValidationError('quoteRequest'),
                    $settings,
                    $prepare,
                    [
                        'oem_id'        => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'type_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'  => 'contact1',
                        'contact_email' => 'contact1@test.com',
                        'contact_phone' => '+27113456789',
                        'message'       => 'message',
                        'files'         => [UploadedFile::fake()->create('document.jpg', 200)],
                    ],
                ],
                'Invalid customer id/name'                  => [
                    new GraphQLValidationError('quoteRequest'),
                    $settings,
                    $prepare,
                    [
                        'oem_id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id'     => null,
                        'customer_custom' => null,
                        'type_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'    => 'contact1',
                        'contact_email'   => 'contact1@test.com',
                        'contact_phone'   => '+27113456789',
                    ],
                ],
                'customer_id and customer_custom'           => [
                    new GraphQLValidationError('quoteRequest'),
                    $settings,
                    $prepare,
                    [
                        'oem_id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'customer_custom' => 'Customer name',
                        'type_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'    => 'contact1',
                        'contact_email'   => 'contact1@test.com',
                        'contact_phone'   => '+27113456789',
                    ],
                ],
                'service_level_id and service_level_custom' => [
                    new GraphQLValidationError('quoteRequest'),
                    $settings,
                    $prepare,
                    [
                        'oem_id'        => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'customer_id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'type_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'contact_name'  => 'contact1',
                        'contact_email' => 'contact1@test.com',
                        'contact_phone' => '+27113456789',
                        'assets'        => [
                            [
                                'asset_id'             => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'duration_id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699af',
                                'service_level_id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a9',
                                'service_level_custom' => 'Custom ServiceLevel',
                            ],
                        ],
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
