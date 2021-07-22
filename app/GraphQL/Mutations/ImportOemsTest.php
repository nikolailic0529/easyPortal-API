<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\DataLoader\Importers\OemsImporter;
use Closure;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\RootUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function __;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\ImportOems
 */
class ImportOemsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param \Closure(): \Illuminate\Http\UploadedFile $fileFactory
     */
    public function testInvoke(
        Response|array $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $fileFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        // Input
        $map   = [];
        $files = [];
        $input = ['file' => null];

        if ($fileFactory) {
            $file     = $fileFactory($this);
            $map[0]   = ['variables.input.file'];
            $files[0] = $file;

            if ($expected instanceof GraphQLSuccess) {
                $this->override(OemsImporter::class, static function (MockInterface $mock) use ($file): void {
                    $mock
                        ->shouldReceive('import')
                        ->with($file->getPathname())
                        ->once()
                        ->andReturn(Command::SUCCESS);
                });
            }
        } else {
            // Lighthouse performs validation BEFORE permission check :(
            //
            // https://github.com/nuwave/lighthouse/issues/1780
            //
            // Following code required to "fix" it
            $file     = UploadedFile::fake()->create('test.xlsx');
            $map[0]   = ['variables.input.file'];
            $files[0] = $file;
        }

        // Test
        $operations = [
            'operationName' => 'importOems',
            'variables'     => ['input' => $input],
            'query'         => /** @lang GraphQL */
                '
                mutation importOems($input: ImportOemsInput!) {
                    importOems(input: $input){
                        result
                    }
                }
                ',
        ];

        $this->multipartGraphQL($operations, $map, $files)->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new RootOrganizationDataProvider('importOems'),
            new RootUserDataProvider('importOems'),
            new ArrayDataProvider([
                'ok'                  => [
                    new GraphQLSuccess('importOems', self::class, [
                        'result' => true,
                    ]),
                    static function (): UploadedFile {
                        return UploadedFile::fake()->create('text.xlsx');
                    },
                ],
                'invalid file format' => [
                    new GraphQLError('importOems', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (): UploadedFile {
                        return UploadedFile::fake()->create('text.txt');
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
