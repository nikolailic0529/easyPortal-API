<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Filesystem\Disks\ClientDisk;
use App\Services\I18n\Storages\ClientTranslations;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery;
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
 * @coversDefaultClass \App\GraphQL\Mutations\DeleteClientTranslations
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class DeleteClientTranslationsTest extends TestCase {
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
            new AuthOrgRootDataProvider('deleteClientTranslations'),
            new AuthRootDataProvider('deleteClientTranslations'),
            new ArrayDataProvider([
                'ok' => [
                    [
                        'response' => new GraphQLSuccess(
                            'deleteClientTranslations',
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
