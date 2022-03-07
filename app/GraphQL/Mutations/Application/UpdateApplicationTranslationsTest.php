<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

use App\GraphQL\Queries\Application\Translations;
use App\Services\Filesystem\Disks\AppDisk;
use App\Services\I18n\Storages\AppTranslations;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\RootUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Application\UpdateApplicationTranslations
 */
class UpdateApplicationTranslationsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param array<string,mixed>                                                   $input
     * @param array<string,array{key: string, value: string, default: string|null}> $defaultTranslations
     * @param array<string, string>                                                 $customTranslations
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        array $defaultTranslations = [],
        array $customTranslations = [],
        array $input = [
            'locale'       => 'en',
            'translations' => [],
        ],
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        if ($defaultTranslations) {
            $this->override(
                Translations::class,
                static function (MockInterface $mock) use ($defaultTranslations): void {
                    $mock
                        ->shouldReceive('getTranslations')
                        ->once()
                        ->andReturn($defaultTranslations);
                },
            );
        }

        if ($customTranslations) {
            $disk    = $this->app()->make(AppDisk::class);
            $storage = new AppTranslations($disk, 'en');

            $storage->save($customTranslations);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ 'mutation updateApplicationTranslations(
                $input: UpdateApplicationTranslationsInput!) {
                    updateApplicationTranslations(input:$input){
                        updated {
                            key
                            value
                            default
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
        $updated = [
            [
                'key'     => 'key1',
                'value'   => 'value1',
                'default' => 'default-value',
            ],
        ];
        $default = [
            [
                'key'     => 'key1',
                'value'   => 'value',
                'default' => 'default-value',
            ],
            [
                'key'     => 'key2',
                'value'   => '123',
                'default' => '12345',
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
                            'updated' => $updated,
                        ],
                    ),
                    $default,
                    [
                        'key1' => 'other key',
                    ],
                    $input,
                ],
                'success - update current value'             => [
                    new GraphQLSuccess(
                        'updateApplicationTranslations',
                        UpdateApplicationTranslations::class,
                        [
                            'updated' => $updated,
                        ],
                    ),
                    $default,
                    [
                        'key1' => 'old',
                    ],
                    $input,
                ],
                'success - retrieve updated duplicate input' => [
                    new GraphQLSuccess(
                        'updateApplicationTranslations',
                        UpdateApplicationTranslations::class,
                        [
                            'updated' => $updated,
                        ],
                    ),
                    $default,
                    [
                        'key1' => 'old',
                    ],
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
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
