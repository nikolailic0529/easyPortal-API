<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Filesystem\Disks\AppDisk;
use App\Services\Filesystem\Storages\AppTranslations;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\RootUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\UpdateApplicationTranslations
 */
class UpdateApplicationTranslationsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param array<string,mixed> $input
     * @param array<mixed>        $translations
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        array $input = [
            'locale'       => 'en',
            'translations' => [],
        ],
        array $translations = [],
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        if ($translations) {
            $disk    = $this->app()->make(AppDisk::class);
            $storage = new AppTranslations($disk, 'en');

            $storage->save($translations);

            $this->app->bind(AppDisk::class, static function () use ($disk): AppDisk {
                return $disk;
            });
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ 'mutation updateApplicationTranslations(
                $input: UpdateApplicationTranslationsInput!) {
                    updateApplicationTranslations(input:$input){
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
            new RootOrganizationDataProvider('updateApplicationTranslations'),
            new RootUserDataProvider('updateApplicationTranslations'),
            new ArrayDataProvider([
                'success - retrieve'                         => [
                    new GraphQLSuccess(
                        'updateApplicationTranslations',
                        UpdateApplicationTranslations::class,
                        [
                            'updated' => $objects,
                        ],
                    ),
                    $input,
                    [
                        'key1' => 'other key',
                    ],
                ],
                'success - update current value'             => [
                    new GraphQLSuccess(
                        'updateApplicationTranslations',
                        UpdateApplicationTranslations::class,
                        [
                            'updated' => $objects,
                        ],
                    ),
                    $input,
                    [
                        'key1' => 'old',
                    ],
                ],
                'success - retrieve updated duplicate input' => [
                    new GraphQLSuccess(
                        'updateApplicationTranslations',
                        UpdateApplicationTranslations::class,
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
                        'key1' => 'old',
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
