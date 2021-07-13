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
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

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
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $documentFactory = null,
        array $input = [
            'document_id' => '',
            'note'        => '',
            'files'       => null,
        ],
    ): void {
        // Prepare
        $user = $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        if ($user) {
            $user->save();
        }

        Storage::fake();

        $map  = [];
        $file = [];

        if (array_key_exists('files', $input)) {
            if (!empty($input['files'])) {
                foreach ($input['files'] as $index => $file) {
                    $file[$index] = $file;
                    $map[$index]  = ["variables.input.files.{$index}"];
                }
                $input['files'] = null;
            }
        }

        if ($documentFactory) {
            $documentFactory($this);
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
                    }
                }
            }';

        $operations = [
            'operationName' => 'createNote',
            'query'         => $query,
            'variables'     => ['input' => $input],
        ];

        $response = $this->multipartGraphQL($operations, $map, $file)->assertThat($expected);
        // $response = $this
        //     ->graphQL(/** @lang GraphQL */ 'mutation createNote($input: CreateNoteInput!) {
        //         createNote(input:$input) {
        //             created {
        //                 id
        //                 note
        //                 created_at
        //                 user {
        //                     given_name
        //                     family_name
        //                     id
        //                 }
        //             }
        //         }
        //     }', ['input' => $data])
        //     ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            $created = $response->json('data.createNote.created');
            $this->assertIsArray($created);
            $this->assertNotNull($created['id']);
            $this->assertNotNull($created['created_at']);
            // $this->assertEquals($data['note'], $created['note']);
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
                'ok' => [
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
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
