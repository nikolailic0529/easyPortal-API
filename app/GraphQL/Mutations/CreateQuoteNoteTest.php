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
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function __;
use function array_key_exists;
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
        Closure $prepare = null,
        array $input = [
            'quote_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
            'note'     => 'note',
            'files'    => null,
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
            Document::factory()->create([
                'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                'type_id'     => $type->getKey(),
                'reseller_id' => $reseller->getKey(),
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

        // Test
        $query = /** @lang GraphQL */
            'mutation createQuoteNote($input: CreateQuoteNoteInput!){
                createQuoteNote(input: $input){
                    created {
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
            'operationName' => 'createQuoteNote',
            'query'         => $query,
            'variables'     => ['input' => $input],
        ];

        $response = $this->multipartGraphQL($operations, $map, $file)->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            $created = $response->json('data.createQuoteNote.created');
            $this->assertIsArray($created);
            $this->assertNotNull($created['id']);
            $this->assertNotNull($created['created_at']);
            $this->assertNotNull($created['updated_at']);
            $this->assertEquals($input['note'], $created['note']);
            $this->assertEquals($user->getKey(), $created['user_id']);
            // Files assertion
            $this->assertCount(1, $created['files']);
            $this->assertEquals('document.csv', $created['files'][0]['name']);
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
                new UserDataProvider('createQuoteNote', [
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'ok'                  => [
                        new GraphQLSuccess('createQuoteNote', CreateQuoteNote::class),
                        $prepare,
                        $input,
                        $settings,
                    ],
                    'Invalid note'        => [
                        new GraphQLError('createQuoteNote', static function (): array {
                            return [__('errors.validation_failed')];
                        }),
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
                new UserDataProvider('createQuoteNote', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('createQuoteNote', CreateQuoteNote::class),
                        $prepare,
                        $input,
                        $settings,
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
