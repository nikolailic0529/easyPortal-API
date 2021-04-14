<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Filesystem\Disks\UIDisk;
use App\Services\Filesystem\Storages\UITranslations;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\RootDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\UpdateApplicationStorageTranslations
 */
class UpdateApplicationStorageTranslationsTest extends TestCase {
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
            $disk    = $this->app()->make(UIDisk::class);
            $storage = new UITranslations($disk, 'en');

            $storage->save($translations);

            $this->app->bind(UIDisk::class, static function () use ($disk): UIDisk {
                return $disk;
            });
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ 'mutation updateApplicationStorageTranslations(
                $input: UpdateApplicationStorageTranslationsInput!) {
                    updateApplicationStorageTranslations(input:$input){
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
            new TenantDataProvider(),
            new RootDataProvider('updateApplicationStorageTranslations'),
            new ArrayDataProvider([
                'success - retrieve'                         => [
                    new GraphQLSuccess(
                        'updateApplicationStorageTranslations',
                        UpdateApplicationStorageTranslations::class,
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
                        'updateApplicationStorageTranslations',
                        UpdateApplicationStorageTranslations::class,
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
                        'updateApplicationStorageTranslations',
                        UpdateApplicationStorageTranslations::class,
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
