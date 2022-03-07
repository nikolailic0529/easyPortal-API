<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

use App\Services\Filesystem\Disks\AppDisk;
use App\Services\I18n\Storages\AppTranslations;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\RootUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function is_array;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Application\DeleteApplicationTranslations
 */
class DeleteApplicationTranslationsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param \LastDragon_ru\LaraASP\Testing\Constraints\Response\Response|array{
     *      response:\LastDragon_ru\LaraASP\Testing\Constraints\Response\Response,
     *      content:array<mixed>
     *      } $expected
     * @param array<mixed>  $content
     * @param array<string> $keys
     */
    public function testInvoke(
        Response|array $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        string $locale = null,
        array $content = [],
        array $keys = [],
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

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
            $this->assertNotNull($expectedResponse);
            $this->assertEquals($expectedContent, $storage->load());
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
            new RootOrganizationDataProvider('deleteApplicationTranslations'),
            new RootUserDataProvider('deleteApplicationTranslations'),
            new ArrayDataProvider([
                'ok' => [
                    [
                        'response' => new GraphQLSuccess(
                            'deleteApplicationTranslations',
                            DeleteApplicationTranslations::class,
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
