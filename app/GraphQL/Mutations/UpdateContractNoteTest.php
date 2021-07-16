<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\Document;
use App\Models\File;
use App\Models\Note;
use App\Models\Organization;
use App\Models\Reseller;
use App\Models\Type;
use App\Models\User;
use Closure;
use Illuminate\Http\UploadedFile;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLUnauthorized;
use Tests\TestCase;

use function __;
use function array_key_exists;
/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\UpdateContractNote
 */
class UpdateContractNoteTest extends TestCase {
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

        if ($prepare) {
            $prepare($this, $organization, $user);
        } else {
            if (!$organization) {
                $organization = $this->setOrganization(Organization::factory()->create());
            }

            if (!$settings) {
                $this->setSettings([
                    'ep.contract_types' => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ac'],
                ]);
            }

            $type     = Type::factory()->create([
                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ac',
            ]);
            $reseller = Reseller::factory()->create([
                'id' => $organization ? $organization->getKey() : $this->faker->uuid,
            ]);

            Document::factory()
                ->hasNotes(1, [
                    'id'              => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                    'organization_id' => $organization->getKey(),
                ])
                ->create([
                    'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                    'type_id'     => $type->getKey(),
                    'reseller_id' => $reseller->getKey(),
                ]);
        }

        $map  = [];
        $file = [];

        $uploadTest = false;

        if (array_key_exists('files', $input)) {
            if (!empty($input['files'])) {
                foreach ($input['files'] as $index => $item) {
                    if ($item['content']) {
                        $uploadTest   = true;
                        $file[$index] = $item;
                        $map[$index]  = ["variables.input.files.{$index}"];
                        unset($input['files'][$index]);
                    }
                }
            }
        }

        // Test
        $query      = /** @lang GraphQL */
            'mutation updateContractNote($input: UpdateContractNoteInput!){
                updateContractNote(input: $input){
                    updated {
                        id
                        note
                        user_id
                        created_at
                        updated_at
                        user {
                            id
                            given_name
                            family_name
                        }
                        files {
                            id
                            name
                            url
                        }
                    }
                }
            }';
        $input      = $input ?: [
            'id'    => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
            'note'  => 'old',
            'files' => null,
        ];
        $operations = [
            'operationName' => 'updateContractNote',
            'query'         => $query,
            'variables'     => ['input' => $input],
        ];

        $response = $this->multipartGraphQL($operations, $map, $file)->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            $updated = $response->json('data.updateContractNote.updated');
            $this->assertIsArray($updated);
            $this->assertNotNull($updated['id']);
            $this->assertNotNull($updated['created_at']);
            $this->assertNotNull($updated['updated_at']);
            $this->assertEquals($input['note'], $updated['note']);
            $this->assertEquals($user->getKey(), $updated['user_id']);
            // Files assertion
            $this->assertCount(1, $updated['files']);
            $uploadTest
                ? $this->assertEquals('new.csv', $updated['files'][0]['name'])
                : $this->assertEquals('keep.csv', $updated['files'][0]['name']);
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
            $type     = Type::factory()->create([
                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
            ]);
            $reseller = Reseller::factory()->create([
                'id' => $organization->getKey(),
            ]);
            $document = Document::factory()
                ->create([
                    'type_id'     => $type->getKey(),
                    'reseller_id' => $reseller->getKey(),
                ]);
            Note::factory()
                ->for($user)
                ->hasFiles(1, [
                    'name' => 'deleted',
                ])
                ->create([
                    'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                    'document_id' => $document->getKey(),
                ]);
        };
        $settings = [
            'ep.file.max_size'  => 250,
            'ep.file.formats'   => ['csv'],
            'ep.contract_types' => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ad'],
        ];
        return (new MergeDataProvider([
            'contracts-view' => new CompositeDataProvider(
                new OrganizationDataProvider('updateContractNote'),
                new UserDataProvider('updateContractNote', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok-files'            => [
                        new GraphQLSuccess('updateContractNote', UpdateContractNote::class),
                        $settings,
                        $prepare,
                        [
                            'note'  => 'new note',
                            'id'    => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            'files' => [
                                [
                                    'id'      => null,
                                    'content' => UploadedFile::fake()->create('new.csv', 200),
                                ],
                            ],
                        ],
                    ],
                    'ok-Ids'              => [
                        new GraphQLSuccess('updateContractNote', UpdateContractNote::class),
                        $settings,
                        static function (TestCase $test, ?Organization $organization, User $user): void {
                            if ($user) {
                                $user->save();
                            }
                            $type     = Type::factory()->create([
                                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                            ]);
                            $reseller = Reseller::factory()->create([
                                'id' => $organization->getKey(),
                            ]);
                            $document = Document::factory()
                                ->create([
                                    'type_id'     => $type->getKey(),
                                    'reseller_id' => $reseller->getKey(),
                                ]);
                            $note     = Note::factory()
                                ->for($user)
                                ->hasFiles(1, [
                                    'name' => 'deleted',
                                ])
                                ->create([
                                    'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                    'document_id' => $document->getKey(),
                                ]);
                            File::factory()->create([
                                'id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a169972',
                                'name'    => 'keep.csv',
                                'note_id' => $note->getKey(),
                            ]);
                        },
                        [
                            'note'  => 'new note',
                            'id'    => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            'files' => [
                                [
                                    'id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a169972',
                                    'content' => null,
                                ],
                            ],
                        ],
                    ],
                    'Invalid note id'     => [
                        new GraphQLError('updateContractNote', static function (): array {
                            return [__('errors.validation_failed')];
                        }),
                        [
                            'ep.contract_types' => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ac'],
                        ],
                        static function (): void {
                            Note::factory()->create([
                                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            ]);
                        },
                        [
                            'note'  => '',
                            'id'    => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a6',
                            'files' => [
                                [
                                    'content' => UploadedFile::fake()->create('document.csv', 200),
                                    'id'      => null,
                                ],
                            ],
                        ],
                    ],
                    'Invalid note text'   => [
                        new GraphQLError('updateContractNote', static function (): array {
                            return [__('errors.validation_failed')];
                        }),
                        [
                            'ep.contract_types' => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ac'],
                        ],
                        static function (): void {
                            Note::factory()->create([
                                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            ]);
                        },
                        [
                            'note'  => '',
                            'id'    => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a6',
                            'files' => [
                                [
                                    'content' => UploadedFile::fake()->create('document.csv', 200),
                                    'id'      => null,
                                ],
                            ],
                        ],
                        [
                            'ep.file.max_size' => 250,
                            'ep.file.formats'  => ['csv'],
                        ],
                    ],
                    'Invalid file size'   => [
                        new GraphQLError('updateContractNote', static function (): array {
                            return [__('errors.validation_failed')];
                        }),
                        [
                            'ep.file.max_size' => 100,
                            'ep.file.formats'  => ['csv'],
                        ],
                        static function (): void {
                            Note::factory()->create([
                                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            ]);
                        },
                        [
                            'note'  => '',
                            'id'    => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a6',
                            'files' => [
                                [
                                    'content' => UploadedFile::fake()->create('document.pdf', 200),
                                    'id'      => null,
                                ],
                            ],
                        ],
                    ],
                    'Invalid file format' => [
                        new GraphQLError('updateContractNote', static function (): array {
                            return [__('errors.validation_failed')];
                        }),
                        [
                            'ep.file.max_size' => 200,
                            'ep.file.formats'  => ['pdf'],
                        ],
                        static function (): void {
                            Note::factory()->create([
                                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            ]);
                        },
                        [
                            'note'  => '',
                            'id'    => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699a6',
                            'files' => [
                                [
                                    'content' => UploadedFile::fake()->create('document.pdf', 250),
                                    'id'      => null,
                                ],
                            ],
                        ],
                    ],
                    'unauthorized'        => [
                        new GraphQLUnauthorized('updateContractNote'),
                        $settings,
                        static function (TestCase $test, ?Organization $organization, User $user): void {
                            $type     = Type::factory()->create([
                                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                            ]);
                            $reseller = Reseller::factory()->create([
                                'id' => $organization->getKey(),
                            ]);
                            $document = Document::factory()
                                ->create([
                                    'type_id'     => $type->getKey(),
                                    'reseller_id' => $reseller->getKey(),
                                ]);
                            $user2    = User::factory()->create();
                            $note     = Note::factory()
                                ->for($user2)
                                ->hasFiles(1, [
                                    'name' => 'deleted',
                                ])
                                ->create([
                                    'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                    'document_id' => $document->getKey(),
                                ]);
                            File::factory()->create([
                                'id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a169972',
                                'name'    => 'keep.csv',
                                'note_id' => $note->getKey(),
                            ]);
                        },
                        [
                            'note'  => 'new note',
                            'id'    => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            'files' => [
                                [
                                    'id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a169972',
                                    'content' => null,
                                ],
                            ],
                        ],
                    ],
                ]),
            ),
            'customers-view' => new CompositeDataProvider(
                new OrganizationDataProvider('updateContractNote'),
                new UserDataProvider('updateContractNote', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok'           => [
                        new GraphQLSuccess('updateContractNote', UpdateContractNote::class),
                        $settings,
                        $prepare,
                        [
                            'note'  => 'new note',
                            'id'    => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            'files' => [
                                [
                                    'id'      => null,
                                    'content' => UploadedFile::fake()->create('new.csv', 200),
                                ],
                            ],
                        ],
                    ],
                    'unauthorized' => [
                        new GraphQLUnauthorized('updateContractNote'),
                        $settings,
                        static function (TestCase $test, ?Organization $organization, User $user): void {
                            $type     = Type::factory()->create([
                                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                            ]);
                            $reseller = Reseller::factory()->create([
                                'id' => $organization->getKey(),
                            ]);
                            $document = Document::factory()
                                ->create([
                                    'type_id'     => $type->getKey(),
                                    'reseller_id' => $reseller->getKey(),
                                ]);
                            $user2    = User::factory()->create();
                            $note     = Note::factory()
                                ->for($user2)
                                ->hasFiles(1, [
                                    'name' => 'deleted',
                                ])
                                ->create([
                                    'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                    'document_id' => $document->getKey(),
                                ]);
                            File::factory()->create([
                                'id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a169972',
                                'name'    => 'keep.csv',
                                'note_id' => $note->getKey(),
                            ]);
                        },
                        [
                            'note'  => 'new note',
                            'id'    => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            'files' => [
                                [
                                    'id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a169972',
                                    'content' => null,
                                ],
                            ],
                        ],
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
