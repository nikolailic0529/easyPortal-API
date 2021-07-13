<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\Document;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function __;
use function array_key_exists;
/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\CreateNote
 */
class CreateNoteTest extends TestCase {
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
        Closure $documentFactory = null,
        array $input = [
            'document_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
            'note'        => 'note',
            'files'       => null,
        ],
        array $settings = [],
    ): void {
        // Prepare
        $user = $this->setUser($userFactory, $this->setOrganization($organizationFactory));
        $this->setSettings($settings);

        if ($user) {
            $user->save();
        }

        Storage::fake();

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

        if ($documentFactory) {
            $documentFactory($this);
        } else {
            Document::factory()->create([
                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
            ]);
        }

        // Test
        $query = /** @lang GraphQL */
            'mutation createNote($input: CreateNoteInput!){
                createNote(input: $input){
                    created {
                        id
                        note
                        created_at
                        user {
                            id
                            given_name
                            family_name
                        }
                        files {
                            id
                            name
                            path
                        }
                    }
                }
            }';

        $operations = [
            'operationName' => 'createNote',
            'query'         => $query,
            'variables'     => ['input' => $input],
        ];

        $response = $this->multipartGraphQL($operations, $map, $file)->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            $created = $response->json('data.createNote.created');
            $this->assertIsArray($created);
            $this->assertNotNull($created['id']);
            $this->assertNotNull($created['created_at']);
            $this->assertEquals($input['note'], $created['note']);
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
        return (new CompositeDataProvider(
            new OrganizationDataProvider('createNote'),
            new AuthUserDataProvider('createNote'),
            new ArrayDataProvider([
                'ok'                  => [
                    new GraphQLSuccess('createNote', CreateNote::class),
                    static function (): void {
                        Document::factory()->create([
                            'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                        ]);
                    },
                    [
                        'note'        => 'note',
                        'document_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                        'files'       => [UploadedFile::fake()->create('document.csv', 200)],
                    ],
                    [
                        'ep.file.max_size' => 250,
                        'ep.file.formats'  => ['csv'],
                    ],
                ],
                'Invalid note'        => [
                    new GraphQLError('createNote', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (): void {
                        Document::factory()->create([
                            'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                        ]);
                    },
                    [
                        'note'        => '',
                        'document_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                        'files'       => [UploadedFile::fake()->create('document.csv', 200)],
                    ],
                ],
                'Invalid document'    => [
                    new GraphQLError('createNote', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (): void {
                        Document::factory()->create([
                            'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                        ]);
                    },
                    [
                        'note'        => 'note',
                        'document_id' => '',
                        'files'       => [UploadedFile::fake()->create('document.csv', 200)],
                    ],
                    [
                        'ep.file.max_size' => 250,
                        'ep.file.formats'  => ['csv'],
                    ],
                ],
                'Invalid file size'   => [
                    new GraphQLError('createNote', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (): void {
                        Document::factory()->create([
                            'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                        ]);
                    },
                    [
                        'note'        => 'note',
                        'document_id' => '',
                        'files'       => [UploadedFile::fake()->create('document.csv', 150)],
                    ],
                    [
                        'ep.file.max_size' => 100,
                        'ep.file.formats'  => ['csv'],
                    ],
                ],
                'Invalid file format' => [
                    new GraphQLError('createNote', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (): void {
                        Document::factory()->create([
                            'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                        ]);
                    },
                    [
                        'note'        => 'note',
                        'document_id' => '',
                        'files'       => [UploadedFile::fake()->create('document.csv', 150)],
                    ],
                    [
                        'ep.file.max_size' => 200,
                        'ep.file.formats'  => ['pdf'],
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
