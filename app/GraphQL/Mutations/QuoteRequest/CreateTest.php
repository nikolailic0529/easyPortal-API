<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\QuoteRequest;

use App\Mail\QuoteRequest;
use App\Models\Asset;
use App\Models\Customer;
use App\Models\Data\Oem;
use App\Models\Data\ServiceGroup;
use App\Models\Data\ServiceLevel;
use App\Models\Data\Type;
use App\Models\Document;
use App\Models\Note;
use App\Models\Organization;
use App\Models\QuoteRequestDuration;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Config\Repository;
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

use function implode;
use function trans;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\QuoteRequest\Create
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
     * @param OrganizationFactory                              $orgFactory
     * @param UserFactory                                      $userFactory
     * @param array<string,mixed>                              $input
     * @param SettingsFactory                                  $settingsFactory
     * @param Closure(static, ?Organization, ?User): void|null $prepare
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
                'customer_id'   => Customer::factory()->ownedBy($org)->create()->getKey(),
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

        $this
            ->graphQL(
            /** @lang GraphQL */
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
                GRAPHQL,
                [
                    'input' => $input,
                ],
            )
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            Mail::assertSent(QuoteRequest::class);
        }
    }

    public function testCreateRequestWithDocuments(): void {
        // Prepare
        $org       = $this->setOrganization(Organization::factory()->create());
        $user      = $this->setUser(User::factory()->create());
        $type      = Type::factory()->create();
        $mutation  = $this->app->make(Create::class);
        $documentA = Document::factory()->ownedBy($org)->create([
            'type_id' => $type,
        ]);
        $documentB = Document::factory()->ownedBy($org)->create([
            'type_id' => $type,
        ]);
        $durationA = QuoteRequestDuration::factory()->create();
        $durationB = QuoteRequestDuration::factory()->create();
        $input     = new CreateInput([
            'contact_name'  => $this->faker->name(),
            'contact_email' => $this->faker->email(),
            'contact_phone' => $this->faker->e164PhoneNumber(),
            'documents'     => [
                [
                    'document_id' => $documentA->getKey(),
                    'duration_id' => $durationA->getKey(),
                ],
                [
                    'document_id' => $documentA->getKey(),
                    'duration_id' => $durationB->getKey(),
                ],
                [
                    'document_id' => $documentB->getKey(),
                    'duration_id' => $durationB->getKey(),
                ],
            ],
        ]);

        $this->setSettings([
            'ep.contract_types' => [$type->getKey()],
        ]);

        // Request
        $request = ($mutation)->createRequest($input);

        self::assertEquals($org->getKey(), $request->organization_id);
        self::assertEquals($user->getKey(), $request->user_id);
        self::assertCount(0, $request->files);
        self::assertCount(0, $request->assets);
        self::assertCount(3, $request->documents);

        // Notes
        $notes = Note::query()
            ->where('quote_request_id', '=', $request->getKey())
            ->get();

        self::assertCount(2, $notes);

        // NoteA
        $noteA = $notes->first(static function (Note $note) use ($documentA): bool {
            return $note->document_id === $documentA->getKey();
        });

        self::assertNotNull($noteA);
        self::assertNull($noteA->note);
        self::assertFalse($noteA->pinned);
        self::assertEquals($org->getKey(), $noteA->organization_id);
        self::assertEquals($user->getKey(), $noteA->user_id);
        self::assertEquals($request->getKey(), $noteA->quote_request_id);
        self::assertEquals($documentA->getKey(), $noteA->document_id);

        // NoteB
        $noteB = $notes->first(static function (Note $note) use ($documentB): bool {
            return $note->document_id === $documentB->getKey();
        });

        self::assertNotNull($noteB);
        self::assertNull($noteB->note);
        self::assertFalse($noteB->pinned);
        self::assertEquals($org->getKey(), $noteB->organization_id);
        self::assertEquals($user->getKey(), $noteB->user_id);
        self::assertEquals($request->getKey(), $noteB->quote_request_id);
        self::assertEquals($documentB->getKey(), $noteB->document_id);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        $type     = 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad';
        $prepare  = static function (TestCase $test, ?Organization $org, ?User $user) use ($type): void {
            $oem  = Oem::factory()->create([
                'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                'key'  => 'key1',
                'name' => 'oem1',
            ]);
            $type = Type::factory()->create([
                'id'          => $type,
                'name'        => 'new',
                'object_type' => (new Document())->getMorphClass(),
            ]);

            Customer::factory()->ownedBy($org)->create([
                'id'              => $org,
                'name'            => 'customer1',
                'assets_count'    => 0,
                'contacts_count'  => 0,
                'locations_count' => 0,
                'changed_at'      => '2021-10-19 10:15:00',
                'synced_at'       => '2021-10-19 10:25:00',
            ]);
            Document::factory()->ownedBy($org)->create([
                'id'      => '047f06f5-e62f-464a-8df8-bd9834e21915',
                'type_id' => $type,
            ]);
            Asset::factory()->ownedBy($org)->create([
                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
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
            new AuthOrgDataProvider('quoteRequest', 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab'),
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
                'Invalid input'                             => [
                    new GraphQLValidationError('quoteRequest', static function (Repository $config): array {
                        return [
                            'input.oem_id'                    => [
                                trans('validation.oem_id'),
                            ],
                            'input.customer_id'               => [
                                trans('validation.customer_id'),
                            ],
                            'input.contact_name'              => [
                                trans('validation.required'),
                            ],
                            'input.contact_email'             => [
                                trans('validation.email'),
                            ],
                            'input.contact_phone'             => [
                                trans('validation.phone'),
                            ],
                            'input.type_id'                   => [
                                trans('validation.quote_type_id'),
                            ],
                            'input.assets.0.asset_id'         => [
                                trans('validation.asset_id'),
                            ],
                            'input.assets.0.duration_id'      => [
                                trans('validation.quote_request_duration_id'),
                            ],
                            'input.assets.0.service_level_id' => [
                                trans('validation.service_level_id'),
                            ],
                            'input.files.0'                   => [
                                trans('validation.max.file', [
                                    'max' => $config->get('ep.file.max_size') ?? 0,
                                ]),
                            ],
                            'input.files.1'                   => [
                                trans('validation.mimes', [
                                    'values' => implode(', ', $config->get('ep.file.formats')),
                                ]),
                            ],
                        ];
                    }),
                    $settings,
                    $prepare,
                    [
                        'oem_id'        => '00000000-0000-0000-0000-000000000000',
                        'customer_id'   => '00000000-0000-0000-0000-000000000000',
                        'type_id'       => '00000000-0000-0000-0000-000000000000',
                        'contact_name'  => '',
                        'contact_email' => 'invalid',
                        'contact_phone' => 'invalid',
                        'assets'        => [
                            [
                                'asset_id'         => '00000000-0000-0000-0000-000000000000',
                                'duration_id'      => '00000000-0000-0000-0000-000000000000',
                                'service_level_id' => '00000000-0000-0000-0000-000000000000',
                            ],
                        ],
                        'files'         => [
                            UploadedFile::fake()->create('document.csv', 300),
                            UploadedFile::fake()->create('document.jpg', 200),
                        ],
                    ],
                ],
                'Invalid customer id/name'                  => [
                    new GraphQLValidationError('quoteRequest', static function (): array {
                        return [
                            'input.customer_id'     => [
                                trans('validation.required_without', [
                                    'values' => 'input.customer_custom',
                                ]),
                            ],
                            'input.customer_custom' => [
                                trans('validation.required_without', [
                                    'values' => 'input.customer_id',
                                ]),
                            ],
                        ];
                    }),
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
                    new GraphQLValidationError('quoteRequest', static function (): array {
                        return [
                            'input.customer_id'     => [
                                trans('validation.prohibited_unless', [
                                    'other'  => 'input.customer_custom',
                                    'values' => 'null',
                                ]),
                            ],
                            'input.customer_custom' => [
                                trans('validation.prohibited_unless', [
                                    'other'  => 'input.customer_id',
                                    'values' => 'null',
                                ]),
                            ],
                        ];
                    }),
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
                    new GraphQLValidationError('quoteRequest', static function (): array {
                        return [
                            'input.assets.0.service_level_id'     => [
                                trans('validation.prohibited_unless', [
                                    'other'  => 'input.assets.0.service_level_custom',
                                    'values' => 'null',
                                ]),
                            ],
                            'input.assets.0.service_level_custom' => [
                                trans('validation.prohibited_unless', [
                                    'other'  => 'input.assets.0.service_level_id',
                                    'values' => 'null',
                                ]),
                            ],
                        ];
                    }),
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
