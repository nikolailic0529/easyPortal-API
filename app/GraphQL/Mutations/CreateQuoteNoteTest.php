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
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function __;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\CreateQuoteNote
 */
class CreateQuoteNoteTest extends TestCase {
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
                    'ep.quote_types' => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ac'],
                ]);
            }

            $type     = Type::factory()->create([
                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ac',
            ]);
            $reseller = Reseller::factory()->create([
                'id' => $organization ? $organization->getKey() : $this->faker->uuid,
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
            'ep.file.max_size' => 250,
            'ep.file.formats'  => ['csv'],
            'ep.quote_types'   => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ad'],
        ];

        return (new MergeDataProvider([
            'quotes-view'    => new CompositeDataProvider(
                new OrganizationDataProvider('createQuoteNote'),
                new OrganizationUserDataProvider('createQuoteNote', [
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'ok'                  => [
                        new GraphQLSuccess('createQuoteNote', CreateQuoteNote::class),
                        $settings,
                        $prepare,
                        $input,
                    ],
                    'Invalid note'        => [
                        new GraphQLError('createQuoteNote', static function (): array {
                            return [__('errors.validation_failed')];
                        }),
                        [
                            'ep.quote_types' => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ac'],
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
                            'ep.quote_types' => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ac'],
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
                            'ep.quote_types' => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ac'],
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
                            'ep.quote_types' => ['f3cb1fac-b454-4f23-bbb4-f3d84a1699ac'],
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
            'customers-view' => new CompositeDataProvider(
                new OrganizationDataProvider('createQuoteNote'),
                new OrganizationUserDataProvider('createQuoteNote', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('createQuoteNote', CreateQuoteNote::class),
                        $settings,
                        $prepare,
                        $input,
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
