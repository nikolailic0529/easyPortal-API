<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

use App\Services\Filesystem\Disks\AppDisk;
use App\Services\I18n\Storages\AppTranslations;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthRootDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

use function is_array;

/**
 * @deprecated Please {@see \App\GraphQL\Mutations\Locale\Update}
 *
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Application\DeleteApplicationTranslations
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class DeleteApplicationTranslationsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param Response|array{response:Response,content:array<mixed>} $expected
     * @param OrganizationFactory                                    $orgFactory
     * @param UserFactory                                            $userFactory
     * @param array<mixed>                                           $content
     * @param array<string>                                          $keys
     */
    public function testInvoke(
        Response|array $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        string $locale = null,
        array $content = [],
        array $keys = [],
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

        // Mock
        $storage = null;

        if ($locale) {
            $disk    = $this->app->make(AppDisk::class);
            $storage = new AppTranslations($disk, $locale);

            $storage->save($content);
        }

        // Test
        $expectedResponse = is_array($expected) ? $expected['response'] : $expected;
        $expectedContent  = is_array($expected) ? $expected['content'] : null;

        $this
            ->graphQL(
            /** @lang GraphQL */
                '
                mutation deleteApplicationTranslations($locale: String!, $keys: [String!]!) {
                    deleteApplicationTranslations(input: {locale: $locale, keys: $keys}) {
                        deleted
                    }
                }',
                [
                    'locale' => $locale ?? 'en',
                    'keys'   => $keys,
                ],
            )
            ->assertThat($expectedResponse);

        if ($expectedContent) {
            self::assertNotNull($expectedResponse);
            self::assertEquals($expectedContent, $storage->load());
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
            new AuthOrgRootDataProvider('deleteApplicationTranslations'),
            new AuthRootDataProvider('deleteApplicationTranslations'),
            new ArrayDataProvider([
                'ok' => [
                    [
                        'response' => new GraphQLSuccess(
                            'deleteApplicationTranslations',
                            [
                                'deleted' => ['a', 'b'],
                            ],
                        ),
                        'content'  => [
                            'c' => 'c',
                        ],
                    ],
                    'en',
                    [
                        'a' => 'a',
                        'b' => 'b',
                        'c' => 'c',
                    ],
                    ['a', 'a', 'b'],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
