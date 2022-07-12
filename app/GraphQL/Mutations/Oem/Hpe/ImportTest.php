<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Oem\Hpe;

use Illuminate\Http\UploadedFile;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLValidationError;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Oem\Hpe\Import
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class ImportTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory            $orgFactory
     * @param UserFactory                    $userFactory
     * @param array{file: UploadedFile}|null $input
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        array $input = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

        $input ??= [
            'file' => UploadedFile::fake()->create('oem.xlsx'),
        ];

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation test($input: OemHpeImportInput!){
                    oem {
                        hpe {
                            import(input: $input) {
                                result
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
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new RootOrganizationDataProvider('oem'),
            new OrganizationUserDataProvider('oem', [
                'administer',
            ]),
            new ArrayDataProvider([
                'ok'                  => [
                    new GraphQLSuccess('oem', null, new JsonFragment('hpe.import', [
                        'result' => true,
                    ])),
                    [
                        'file' => UploadedFile::fake()->createWithContent(
                            'file.xlsx',
                            $this->getTestData()->content('.xlsx'),
                        ),
                    ],
                ],
                'invalid file format' => [
                    new GraphQLValidationError('oem'),
                    [
                        'file' => UploadedFile::fake()->create('file.txt'),
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
