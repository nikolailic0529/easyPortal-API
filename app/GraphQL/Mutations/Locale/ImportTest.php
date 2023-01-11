<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Locale;

use App\Services\I18n\I18n;
use App\Services\I18n\Storages\Spreadsheet;
use Illuminate\Http\UploadedFile;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthRootDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLValidationError;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

use function trans;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\Locale\Import
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class ImportTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory                    $orgFactory
     * @param UserFactory                            $userFactory
     * @param array{translations: UploadedFile}|null $input
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        string $locale = null,
        array $input = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

        $locale ??= 'en_GB';
        $input  ??= [
            'translations' => UploadedFile::fake()->create('translations.xlsx'),
        ];

        // Mock
        if ($expected instanceof GraphQLSuccess && $locale) {
            $this->override(
                I18n::class,
                static function (MockInterface $mock) use ($locale, $input): void {
                    $expected = (new Spreadsheet($input['translations']))->load();

                    self::assertNotEmpty($expected);

                    $mock
                        ->shouldReceive('setTranslations')
                        ->with($locale, $expected)
                        ->once()
                        ->andReturn(true);
                },
            );
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation test($locale: String!, $input: LocaleImportInput!){
                    locale(name: $locale) {
                        import(input: $input) {
                            result
                        }
                    }
                }
                GRAPHQL,
                [
                    'locale' => $locale,
                    'input'  => $input,
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
            new AuthOrgRootDataProvider('locale'),
            new AuthRootDataProvider('locale'),
            new ArrayDataProvider([
                'ok'             => [
                    new GraphQLSuccess('locale', new JsonFragment('import.result', true)),
                    'en_GB',
                    [
                        'translations' => UploadedFile::fake()->createWithContent(
                            'translations.xlsx',
                            $this->getTestData()->content('.xlsx'),
                        ),
                    ],
                ],
                'invalid locale' => [
                    new GraphQLValidationError('locale', static function (): array {
                        return [
                            'name' => [
                                trans('validation.locale'),
                            ],
                        ];
                    }),
                    'invalid',
                    null,
                ],
                'invalid file'   => [
                    new GraphQLValidationError('locale', static function (): array {
                        return [
                            'input.translations' => [
                                trans('validation.mimes', [
                                    'values' => 'xlsx',
                                ]),
                            ],
                        ];
                    }),
                    'en_GB',
                    [
                        'translations' => UploadedFile::fake()->create('translations.txt'),
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
