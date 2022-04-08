<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\Document;
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
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithSettings;
use Tests\WithUser;

use function __;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\CreateQuoteNote
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class CreateQuoteNoteTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
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
                $org = $this->setOrganization(Organization::factory()->make());
            }

            if (!$settingsFactory) {
                $this->setSettings([
                    'ep.document_statuses_hidden' => [],
                    'ep.quote_types'              => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ac'],
                ]);
            }

            $type     = Type::factory()->create([
                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ac',
            ]);
            $reseller = Reseller::factory()->create([
                'id' => $org ? $org->getKey() : $this->faker->uuid(),
            ]);
            Document::factory()->create([
                'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                'type_id'     => $type->getKey(),
                'reseller_id' => $reseller->getKey(),
            ]);
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
            'mutation createQuoteNote($input: CreateQuoteNoteInput!){
                createQuoteNote(input: $input){
                    created {
                        id
                        pinned
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
            'quote_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
            'note'     => 'note',
            'files'    => null,
        ];
        $operations = [
            'operationName' => 'createQuoteNote',
            'query'         => $query,
            'variables'     => ['input' => $input],
        ];
        $response   = $this->multipartGraphQL($operations, $map, $file)->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            $created = $response->json('data.createQuoteNote.created');
            self::assertIsArray($created);
            self::assertNotNull($created['id']);
            self::assertNotNull($created['created_at']);
            self::assertNotNull($created['updated_at']);
            self::assertEquals($input['pinned'], $created['pinned']);
            self::assertEquals($input['note'], $created['note']);
            self::assertEquals($user->getKey(), $created['user_id']);
            // Files assertion
            self::assertCount(1, $created['files']);
            self::assertEquals('document.csv', $created['files'][0]['name']);
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
            Document::factory()->create([
                'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                'type_id'     => $type->getKey(),
                'reseller_id' => $reseller->getKey(),
            ]);
        };
        $input    = [
            'note'     => 'note',
            'pinned'   => true,
            'quote_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
            'files'    => [UploadedFile::fake()->create('document.csv', 200)],
        ];
        $settings = [
            'ep.file.max_size'            => 250,
            'ep.file.formats'             => ['csv'],
            'ep.quote_types'              => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ad'],
            'ep.document_statuses_hidden' => [],
        ];

        return (new MergeDataProvider([
            'quotes-view' => new CompositeDataProvider(
                new AuthOrgDataProvider('createQuoteNote'),
                new OrgUserDataProvider('createQuoteNote', [
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'ok'                  => [
                        new GraphQLSuccess('createQuoteNote'),
                        $settings,
                        $prepare,
                        $input,
                    ],
                    'Invalid note'        => [
                        new GraphQLError('createQuoteNote', static function (): array {
                            return [__('errors.validation_failed')];
                        }),
                        [
                            'ep.document_statuses_hidden' => [],
                            'ep.quote_types'              => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ac'],
                        ],
                        static function (): void {
                            Document::factory()->create([
                                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            ]);
                        },
                        [
                            'note'     => '',
                            'quote_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            'files'    => [UploadedFile::fake()->create('document.csv', 200)],
                        ],
                    ],
                    'Invalid document'    => [
                        new GraphQLError('createQuoteNote', static function (): array {
                            return [__('errors.validation_failed')];
                        }),
                        [
                            'ep.document_statuses_hidden' => [],
                            'ep.quote_types'              => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ac'],
                        ],
                        static function (): void {
                            Document::factory()->create([
                                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            ]);
                        },
                        [
                            'note'     => 'note',
                            'quote_id' => '',
                            'files'    => [UploadedFile::fake()->create('document.csv', 200)],
                        ],
                        [
                            'ep.file.max_size' => 250,
                            'ep.file.formats'  => ['csv'],
                        ],
                    ],
                    'Invalid file size'   => [
                        new GraphQLError('createQuoteNote', static function (): array {
                            return [__('errors.validation_failed')];
                        }),
                        [
                            'ep.document_statuses_hidden' => [],
                            'ep.quote_types'              => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ac'],
                        ],
                        static function (): void {
                            Document::factory()->create([
                                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            ]);
                        },
                        [
                            'note'     => 'note',
                            'quote_id' => '',
                            'files'    => [UploadedFile::fake()->create('document.csv', 150)],
                        ],
                        [
                            'ep.file.max_size' => 100,
                            'ep.file.formats'  => ['csv'],
                        ],
                    ],
                    'Invalid file format' => [
                        new GraphQLError('createQuoteNote', static function (): array {
                            return [__('errors.validation_failed')];
                        }),
                        [
                            'ep.document_statuses_hidden' => [],
                            'ep.quote_types'              => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ac'],
                        ],
                        static function (): void {
                            Document::factory()->create([
                                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            ]);
                        },
                        [
                            'note'     => 'note',
                            'quote_id' => '',
                            'files'    => [UploadedFile::fake()->create('document.csv', 150)],
                        ],
                        [
                            'ep.file.max_size' => 200,
                            'ep.file.formats'  => ['pdf'],
                        ],
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
