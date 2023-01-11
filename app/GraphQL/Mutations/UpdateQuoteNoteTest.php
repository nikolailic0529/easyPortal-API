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
class UpdateQuoteNoteTest extends TestCase {
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
            $data = [
                'id'              => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                'organization_id' => $org->getKey(),
            ];

            Document::factory()
                ->ownedBy($org)
                ->hasNotes(1, $data)
                ->create([
                    'id'      => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                    'type_id' => $type->getKey(),
                ]);
        }

        $map  = [];
        $file = [];

        $uploadTest = false;

        if (isset($input['files'])) {
            foreach ((array) $input['files'] as $index => $item) {
                if (isset($item['content']) && $item['content']) {
                    $uploadTest   = true;
                    $file[$index] = $item;
                    $map[$index]  = ["variables.input.files.{$index}"];

                    unset($input['files'][$index]);
                }
            }
        }

        // Test
        $query = /** @lang GraphQL */
            'mutation updateQuoteNote($input: UpdateQuoteNoteInput!){
                updateQuoteNote(input: $input){
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
            'operationName' => 'updateQuoteNote',
            'query'         => $query,
            'variables'     => ['input' => $input],
        ];

        $response = $this->multipartGraphQL($operations, $map, $file)->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            $updated = $response->json('data.updateQuoteNote.updated');
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
            self::assertCount(1, $updated['files']);
            $uploadTest
                ? self::assertEquals('new.csv', $updated['files'][0]['name'])
                : self::assertEquals('keep.csv', $updated['files'][0]['name']);
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
            $document = Document::factory()
                ->ownedBy($org)
                ->create([
                    'type_id' => $type->getKey(),
                ]);
            Note::factory()
                ->ownedBy($org)
                ->hasFiles(1, [
                    'name' => 'deleted',
                ])
                ->create([
                    'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                    'user_id'     => $user,
                    'document_id' => $document->getKey(),
                ]);
        };
        $settings = [
            'ep.file.max_size'            => 250,
            'ep.file.formats'             => ['csv'],
            'ep.quote_types'              => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ad'],
            'ep.document_statuses_hidden' => [],
        ];

        return (new MergeDataProvider([
            'quotes-view' => new CompositeDataProvider(
                new AuthOrgDataProvider('updateQuoteNote'),
                new OrgUserDataProvider('updateQuoteNote', [
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'ok-files'            => [
                        new GraphQLSuccess('updateQuoteNote'),
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
                    ],
                    'ok-Ids'              => [
                        new GraphQLSuccess('updateQuoteNote'),
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
                                ->hasFiles(1, [
                                    'name' => 'deleted',
                                ])
                                ->create([
                                    'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                    'user_id'     => $user,
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
                    ],
                    'optional note'       => [
                        new GraphQLSuccess('updateQuoteNote'),
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
                    ],
                    'optional pinned'     => [
                        new GraphQLSuccess('updateQuoteNote'),
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
                    ],
                    'optional files'      => [
                        new GraphQLSuccess('updateQuoteNote'),
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
                    ],
                    'Invalid note id'     => [
                        new GraphQLError('updateQuoteNote', static function (): array {
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
                        new GraphQLError('updateQuoteNote', static function (): array {
                            return [trans('errors.validation_failed')];
                        }),
                        [
                            'ep.file.max_size' => 250,
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
                                    'content' => UploadedFile::fake()->create('document.csv', 200),
                                    'id'      => null,
                                ],
                            ],
                        ],
                    ],
                    'Invalid file size'   => [
                        new GraphQLError('updateQuoteNote', static function (): array {
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
                        new GraphQLError('updateQuoteNote', static function (): array {
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
                        new GraphQLUnauthorized('updateQuoteNote'),
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
                        new GraphQLUnauthorized('updateQuoteNote'),
                        $settings,
                        static function (TestCase $test, ?Organization $org, ?User $user): Note {
                            return Note::factory()->ownedBy($org)->create([
                                'id'      => 'eda1284f-3f33-431d-be55-efcf3da2fd3f',
                                'user_id' => $user,
                                'note'    => null,
                            ]);
                        },
                        [
                            'id'   => 'eda1284f-3f33-431d-be55-efcf3da2fd3f',
                            'note' => 'new note',
                        ],
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
