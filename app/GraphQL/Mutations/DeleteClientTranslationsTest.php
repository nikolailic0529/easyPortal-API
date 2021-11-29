<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Filesystem\Disks\ClientDisk;
use App\Services\I18n\Storages\ClientTranslations;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\RootUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function is_array;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\DeleteClientTranslations
 */
class DeleteClientTranslationsTest extends TestCase {
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
            $disk    = $this->app->make(ClientDisk::class);
            $storage = new ClientTranslations($disk, $locale);

            $storage->save($content);

            $mutation = Mockery::mock(DeleteClientTranslations::class);
            $mutation->makePartial();
            $mutation->shouldAllowMockingProtectedMethods();
            $mutation
                ->shouldReceive('getStorage')
                ->once()
                ->andReturn($storage);

            $this->app->bind(DeleteClientTranslations::class, static function () use ($mutation) {
                return $mutation;
            });
        }

        // Test
        $expectedResponse = is_array($expected) ? $expected['response'] : $expected;
        $expectedContent  = is_array($expected) ? $expected['content'] : null;

        $this
            ->graphQL(
                /** @lang GraphQL */
                '
                mutation deleteClientTranslations($locale: String!, $keys: [String!]!) {
                    deleteClientTranslations(input: {locale: $locale, keys: $keys}) {
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
            new RootOrganizationDataProvider('deleteClientTranslations'),
            new RootUserDataProvider('deleteClientTranslations'),
            new ArrayDataProvider([
                'ok' => [
                    [
                        'response' => new GraphQLSuccess(
                            'deleteClientTranslations',
                            DeleteClientTranslations::class,
                            [
                                'deleted' => ['a', 'b'],
                            ],
                        ),
                        'content'  => [
                            [
                                'key'   => 'c',
                                'value' => 'c',
                            ],
                        ],
                    ],
                    'en',
                    [
                        [
                            'key'   => 'a',
                            'value' => 'a',
                        ],
                        [
                            'key'   => 'b',
                            'value' => 'b',
                        ],
                        [
                            'key'   => 'c',
                            'value' => 'c',
                        ],
                    ],
                    ['a', 'a', 'b'],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
