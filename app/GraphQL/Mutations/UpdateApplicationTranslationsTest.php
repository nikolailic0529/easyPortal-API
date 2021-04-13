<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use Closure;
use Illuminate\Support\Facades\Storage;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\RootDataProvider;
use Tests\DataProviders\TenantDataProvider;
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
     */
    public function testInvoke(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        array $input = [
            'locale'       => 'en',
            'translations' => [],
        ],
        string $json = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        if ($json) {
            $mutation = $this->app->make(UpdateApplicationTranslations::class);
            $disc     = $mutation->getDisc()->getValue();
            Storage::fake($disc)->put($mutation->getFile('en'), $json);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ 'mutation updateApplicationTranslations(
                $input: UpdateApplicationTranslationsInput!) {
                    updateApplicationTranslations(input:$input){
                        translations {
                            key
                            value
                        }
                    }
            }', [ 'input' => $input ])
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
            new RootDataProvider('updateApplicationTranslations'),
            new ArrayDataProvider([
                'success - retrieve'                         => [
                    new GraphQLSuccess(
                        'updateApplicationTranslations',
                        UpdateApplicationTranslations::class,
                        [
                            'translations' => $objects,
                        ],
                    ),
                    $input,
                    '{"key1":"other key"}',
                ],
                'success - update current value'             => [
                    new GraphQLSuccess(
                        'updateApplicationTranslations',
                        UpdateApplicationTranslations::class,
                        [
                            'translations' => $objects,
                        ],
                    ),
                    $input,
                    '{"key1":"old"}',
                ],
                'success - retrieve updated duplicate input' => [
                    new GraphQLSuccess(
                        'updateApplicationTranslations',
                        UpdateApplicationTranslations::class,
                        [
                            'translations' => $objects,
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
                    '{"key1":"old"}',
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
