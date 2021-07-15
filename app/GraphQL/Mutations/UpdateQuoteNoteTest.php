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
use Tests\TestCase;

use function __;
use function array_key_exists;
/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\UpdateQuoteNote
 */
class UpdateQuoteNoteTest extends TestCase {
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
        Closure $prepare = null,
        array $input = [
            'id'    => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
            'note'  => 'old',
            'files' => null,
        ],
        array $settings = [
            'ep.quote_types' => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ac'],
        ],
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);
        $this->setSettings($settings);

        if ($prepare) {
            $prepare($this, $organization, $user);
        } else {
            // For validation as it will throw validation errors that will alter test results
            // PROBLEM: it still throws unknown organization in case of no organization
            $type     = Type::factory()->create([
                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ac',
            ]);
            $reseller = Reseller::factory()->create([
                'id' => $organization ? $organization->getKey() : $this->faker->uuid,
            ]);
            $data     = ['id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa'];
            if ($organization) {
                $data['organization_id'] = $organization->getKey();
            }
            Document::factory()
                ->hasNotes(1, $data)
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
        $query = /** @lang GraphQL */
            'mutation updateQuoteNote($input: UpdateQuoteNoteInput!){
                updateQuoteNote(input: $input){
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

        $operations = [
            'operationName' => 'updateQuoteNote',
            'query'         => $query,
            'variables'     => ['input' => $input],
        ];

        $response = $this->multipartGraphQL($operations, $map, $file)->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            $updated = $response->json('data.updateQuoteNote.updated');
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
                ->hasFiles(1, [
                    'name' => 'deleted',
                ])
                ->create([
                    'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                    'document_id' => $document->getKey(),
                ]);
        };
        $settings = [
            'ep.file.max_size' => 250,
            'ep.file.formats'  => ['csv'],
            'ep.quote_types'   => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ad'],
        ];
        return (new MergeDataProvider([
            'quotes-view'    => new CompositeDataProvider(
                new OrganizationDataProvider('updateQuoteNote'),
                new UserDataProvider('updateQuoteNote', [
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'ok-files'            => [
                        new GraphQLSuccess('updateQuoteNote', UpdateContractNote::class),
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
                        $settings,
                    ],
                    'ok-Ids'              => [
                        new GraphQLSuccess('updateQuoteNote', UpdateContractNote::class),
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
                        $settings,
                    ],
                    'Invalid note id'     => [
                        new GraphQLError('updateQuoteNote', static function (): array {
                            return [__('errors.validation_failed')];
                        }),
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
                        new GraphQLError('updateQuoteNote', static function (): array {
                            return [__('errors.validation_failed')];
                        }),
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
                        new GraphQLError('updateQuoteNote', static function (): array {
                            return [__('errors.validation_failed')];
                        }),
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
                        [
                            'ep.file.max_size' => 100,
                            'ep.file.formats'  => ['csv'],
                        ],
                    ],
                    'Invalid file format' => [
                        new GraphQLError('updateQuoteNote', static function (): array {
                            return [__('errors.validation_failed')];
                        }),
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
                        [
                            'ep.file.max_size' => 200,
                            'ep.file.formats'  => ['pdf'],
                        ],
                    ],
                ]),
            ),
            'customers-view' => new CompositeDataProvider(
                new OrganizationDataProvider('updateQuoteNote'),
                new UserDataProvider('updateQuoteNote', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('updateQuoteNote', UpdateContractNote::class),
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
                        $settings,
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
