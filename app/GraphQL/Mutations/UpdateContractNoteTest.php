<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\Data\Type;
use App\Models\Document;
use App\Models\File;
use App\Models\Note;
use App\Models\Organization;
use App\Models\User;
use Closure;
use Illuminate\Http\UploadedFile;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLUnauthorized;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithSettings;
use Tests\WithUser;

use function array_key_exists;
use function trans;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\UpdateContractNote
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class UpdateContractNoteTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     * @param SettingsFactory     $settingsFactory
     * @param array<string,mixed> $input
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $prepare = null,
        array $input = [],
        string $filename = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        $this->setSettings($settingsFactory);

        if ($prepare) {
            $prepare($this, $org, $user);
        } else {
            // Lighthouse performs validation BEFORE permission check :(
            //
            // https://github.com/nuwave/lighthouse/issues/1780
            //
            // Following code required to "fix" it
            if (!$org) {
                $org = $this->setOrganization(Organization::factory()->create());
            }

            if (!$settingsFactory) {
                $this->setSettings([
                    'ep.document_statuses_hidden' => [],
                    'ep.contract_types'           => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ac'],
                ]);
            }

            $type = Type::factory()->create([
                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ac',
            ]);

            Document::factory()
                ->ownedBy($org)
                ->hasNotes(1, [
                    'id'              => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                    'organization_id' => $org,
                ])
                ->create([
                    'id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                    'type_id' => $type->getKey(),
                ]);
        }

        $map  = [];
        $file = [];

        if (isset($input['files'])) {
            foreach ((array) $input['files'] as $index => $item) {
                if (isset($item['content']) && $item['content']) {
                    $file[$index] = $item;
                    $map[$index]  = ["variables.input.files.{$index}"];

                    unset($input['files'][$index]);
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
                        pinned
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
        $input      = $input
            ?: [
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
            self::assertIsArray($updated);
            self::assertNotNull($updated['id']);
            self::assertNotNull($updated['created_at']);
            self::assertNotNull($updated['updated_at']);
            self::assertEquals($user->getKey(), $updated['user_id']);
            array_key_exists('note', $input) && self::assertEquals($input['note'], $updated['note']);
            array_key_exists('pinned', $input)
                ? self::assertEquals($input['pinned'], $updated['pinned'])
                : self::assertFalse($updated['pinned']);
            // Files assertion
            if ($filename) {
                self::assertCount(1, $updated['files']);
                self::assertEquals($filename, $updated['files'][0]['name']);
            } else {
                self::assertEmpty($updated['files']);
            }
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        $prepare  = static function (TestCase $test, ?Organization $org, User $user): void {
            $type     = Type::factory()->create([
                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
            ]);
            $document = Document::factory()->ownedBy($org)->create([
                'type_id' => $type->getKey(),
            ]);
            Note::factory()
                ->ownedBy($org)
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
            'ep.file.max_size'            => 250,
            'ep.file.formats'             => ['csv'],
            'ep.contract_types'           => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ad'],
            'ep.document_statuses_hidden' => [],
        ];

        return (new MergeDataProvider([
            'contracts-view' => new CompositeDataProvider(
                new AuthOrgDataProvider('updateContractNote'),
                new OrgUserDataProvider('updateContractNote', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok-files'            => [
                        new GraphQLSuccess('updateContractNote'),
                        $settings,
                        $prepare,
                        [
                            'note'   => 'new note',
                            'pinned' => true,
                            'id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            'files'  => [
                                [
                                    'id'      => null,
                                    'content' => UploadedFile::fake()->create('new.csv', 200),
                                ],
                            ],
                        ],
                        'new.csv',
                    ],
                    'ok-Ids'              => [
                        new GraphQLSuccess('updateContractNote'),
                        $settings,
                        static function (TestCase $test, ?Organization $org, User $user): void {
                            $type     = Type::factory()->create([
                                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                            ]);
                            $document = Document::factory()
                                ->ownedBy($org)
                                ->create([
                                    'type_id' => $type->getKey(),
                                ]);
                            $note     = Note::factory()
                                ->ownedBy($org)
                                ->for($user)
                                ->hasFiles(1, [
                                    'name' => 'deleted',
                                ])
                                ->create([
                                    'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                    'document_id' => $document->getKey(),
                                ]);
                            File::factory()->create([
                                'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a169972',
                                'name'        => 'keep.csv',
                                'object_id'   => $note->getKey(),
                                'object_type' => $note->getMorphClass(),
                            ]);
                        },
                        [
                            'note'   => 'new note',
                            'pinned' => true,
                            'id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            'files'  => [
                                [
                                    'id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a169972',
                                    'content' => null,
                                ],
                            ],
                        ],
                        'keep.csv',
                    ],
                    'ok-empty files'      => [
                        new GraphQLSuccess('updateContractNote'),
                        $settings,
                        $prepare,
                        [
                            'note'   => 'new note',
                            'pinned' => true,
                            'id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            'files'  => [],
                        ],
                    ],
                    'optional note'       => [
                        new GraphQLSuccess('updateContractNote'),
                        $settings,
                        $prepare,
                        [
                            'id'    => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            'files' => [
                                [
                                    'id'      => null,
                                    'content' => UploadedFile::fake()->create('new.csv', 200),
                                ],
                            ],
                        ],
                        'new.csv',
                    ],
                    'optional pinned'     => [
                        new GraphQLSuccess('updateContractNote'),
                        $settings,
                        $prepare,
                        [
                            'id'    => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            'note'  => 'new note',
                            'files' => [
                                [
                                    'id'      => null,
                                    'content' => UploadedFile::fake()->create('new.csv', 200),
                                ],
                            ],
                        ],
                        'new.csv',
                    ],
                    'optional files'      => [
                        new GraphQLSuccess('updateContractNote'),
                        $settings,
                        static function (TestCase $test, ?Organization $org, User $user): void {
                            $type     = Type::factory()->create([
                                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                            ]);
                            $document = Document::factory()->ownedBy($org)->create([
                                'type_id' => $type->getKey(),
                            ]);
                            // File should not be deleted if files is not updated
                            Note::factory()
                                ->ownedBy($org)
                                ->for($user)
                                ->hasFiles(1, [
                                    'name' => 'keep.csv',
                                ])
                                ->create([
                                    'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                    'document_id' => $document->getKey(),
                                ]);
                        },
                        [
                            'id'     => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            'note'   => 'new note',
                            'pinned' => false,
                        ],
                        'keep.csv',
                    ],
                    'Invalid note id'     => [
                        new GraphQLError('updateContractNote', static function (): array {
                            return [trans('errors.validation_failed')];
                        }),
                        [
                            'ep.document_statuses_hidden' => [],
                            'ep.contract_types'           => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ac'],
                        ],
                        static function (TestCase $test, ?Organization $org): void {
                            Note::factory()->ownedBy($org)->create([
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
                            return [trans('errors.validation_failed')];
                        }),
                        [
                            'ep.document_statuses_hidden' => [],
                            'ep.contract_types'           => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ac'],
                        ],
                        static function (TestCase $test, ?Organization $org): void {
                            Note::factory()->ownedBy($org)->create([
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
                    'Invalid file size'   => [
                        new GraphQLError('updateContractNote', static function (): array {
                            return [trans('errors.validation_failed')];
                        }),
                        [
                            'ep.file.max_size' => 100,
                            'ep.file.formats'  => ['csv'],
                        ],
                        static function (TestCase $test, ?Organization $org): void {
                            Note::factory()->ownedBy($org)->create([
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
                            return [trans('errors.validation_failed')];
                        }),
                        [
                            'ep.file.max_size' => 200,
                            'ep.file.formats'  => ['pdf'],
                        ],
                        static function (TestCase $test, ?Organization $org): void {
                            Note::factory()->ownedBy($org)->create([
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
                        static function (TestCase $test, ?Organization $org, User $user): void {
                            $type     = Type::factory()->create([
                                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                            ]);
                            $document = Document::factory()
                                ->ownedBy($org)
                                ->create([
                                    'type_id' => $type->getKey(),
                                ]);
                            $user2    = User::factory()->create();
                            $note     = Note::factory()
                                ->ownedBy($org)
                                ->for($user2)
                                ->hasFiles(1, [
                                    'name' => 'deleted',
                                ])
                                ->create([
                                    'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                    'document_id' => $document->getKey(),
                                ]);
                            File::factory()->create([
                                'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a169972',
                                'name'        => 'keep.csv',
                                'object_id'   => $note->getKey(),
                                'object_type' => $note->getMorphClass(),
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
                    'system note'         => [
                        new GraphQLUnauthorized('updateContractNote'),
                        $settings,
                        static function (TestCase $test, ?Organization $org, ?User $user): Note {
                            return Note::factory()->ownedBy($org)->create([
                                'id'      => '6a68dec5-30b9-4e8d-8ed5-8a1aa4d19f96',
                                'user_id' => $user,
                                'note'    => null,
                            ]);
                        },
                        [
                            'id'   => '6a68dec5-30b9-4e8d-8ed5-8a1aa4d19f96',
                            'note' => 'new note',
                        ],
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
