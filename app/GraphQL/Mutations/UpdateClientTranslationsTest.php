<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Filesystem\Disks\ClientDisk;
use App\Services\Filesystem\Storages\ClientTranslations;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Tenants\TenantDataProvider;
use Tests\DataProviders\GraphQL\Users\RootUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\UpdateClientTranslations
 */
class UpdateClientTranslationsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param array<string,mixed> $input
     *
     * @param array<string,mixed> $translations
     */
    public function testInvoke(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        array $input = [
            'locale'       => 'en',
            'translations' => [],
        ],
        array $translations = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        if ($translations) {
            $disk    = $this->app()->make(ClientDisk::class);
            $storage = new ClientTranslations($disk, 'en');

            $storage->save($translations);

            $this->app->bind(ClientDisk::class, static function () use ($disk): ClientDisk {
                return $disk;
            });
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ 'mutation updateClientTranslations(
                $input: UpdateClientTranslationsInput!) {
                    updateClientTranslations(input:$input){
                        updated {
                            key
                            value
                        }
                    }
            }', ['input' => $input])
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        $input   = [
            'locale'       => 'en',
            'translations' => [
                [
                    'key'   => 'key1',
                    'value' => 'value1',
                ],
            ],
        ];
        $objects = [
            [
                'key'   => 'key1',
                'value' => 'value1',
            ],
        ];

        return (new CompositeDataProvider(
            new TenantDataProvider('updateClientTranslations'),
            new RootUserDataProvider('updateClientTranslations'),
            new ArrayDataProvider([
                'success - retrieve'                         => [
                    new GraphQLSuccess(
                        'updateClientTranslations',
                        UpdateClientTranslations::class,
                        [
                            'updated' => $objects,
                        ],
                    ),
                    $input,
                    [
                        [
                            'key'   => 'other key',
                            'value' => 'other value',
                        ],
                    ],
                ],
                'success - update current value'             => [
                    new GraphQLSuccess(
                        'updateClientTranslations',
                        UpdateClientTranslations::class,
                        [
                            'updated' => $objects,
                        ],
                    ),
                    $input,
                    [
                        [
                            'key'   => 'key1',
                            'value' => 'old',
                        ],
                    ],
                ],
                'success - retrieve updated duplicate input' => [
                    new GraphQLSuccess(
                        'updateClientTranslations',
                        UpdateClientTranslations::class,
                        [
                            'updated' => $objects,
                        ],
                    ),
                    [
                        'locale'       => 'en',
                        'translations' => [
                            [
                                'key'   => 'key1',
                                'value' => 'overwritten',
                            ],
                            [
                                'key'   => 'key1',
                                'value' => 'value1',
                            ],
                        ],
                    ],
                    [
                        [
                            'key'   => 'key1',
                            'value' => 'old',
                        ],
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
