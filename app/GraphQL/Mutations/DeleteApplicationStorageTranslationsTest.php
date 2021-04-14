<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Filesystem\Disks\UIDisk;
use App\Services\Filesystem\Storages\UITranslations;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery;
use Tests\DataProviders\GraphQL\RootDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function is_array;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\DeleteApplicationStorageTranslations
 */
class DeleteApplicationStorageTranslationsTest extends TestCase {
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
        Closure $tenantFactory,
        Closure $userFactory = null,
        string $locale = null,
        array $content = [],
        array $keys = [],
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        // Mock
        $storage = null;

        if ($locale) {
            $disk    = $this->app->make(UIDisk::class);
            $storage = new UITranslations($disk, $locale);

            $storage->save($content);

            $mutation = Mockery::mock(DeleteApplicationStorageTranslations::class);
            $mutation->makePartial();
            $mutation->shouldAllowMockingProtectedMethods();
            $mutation
                ->shouldReceive('getStorage')
                ->once()
                ->andReturn($storage);

            $this->app->bind(DeleteApplicationStorageTranslations::class, static function () use ($mutation) {
                return $mutation;
            });
        }

        // Test
        $expectedResponse = is_array($expected) ? $expected['response'] : $expected;
        $expectedContent  = is_array($expected) ? $expected['content'] : null;

        $this
            ->graphQL(
            /** @lang GraphQL */ '
                mutation deleteApplicationStorageTranslations($locale: String!, $keys: [String!]!) {
                    deleteApplicationStorageTranslations(input: {locale: $locale, keys: $keys}) {
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
            new TenantDataProvider(),
            new RootDataProvider('deleteApplicationStorageTranslations'),
            new ArrayDataProvider([
                'ok' => [
                    [
                        'response' => new GraphQLSuccess(
                            'deleteApplicationStorageTranslations',
                            DeleteApplicationStorageTranslations::class,
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
