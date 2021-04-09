<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\GraphQL\Mutations\UpdateApplicationStorageTranslations;
use Closure;
use Exception;
use Illuminate\Support\Facades\Storage;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\AnyDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function is_array;
use function json_encode;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Application\StorageTranslations
*/
class StorageTranslationsTest extends TestCase {
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     *
     * @param  array<mixed> $expected
    */
    public function testInvoke(array|string $expected, string $json, string $locale): void {
        if (!is_array($expected)) {
            // Didn't use $expected exception object since __ or trans() are not initialized yet in dataProviders
            $this->expectException($expected);
        }
        $mutation = $this->app->make(UpdateApplicationStorageTranslations::class);
        Storage::fake($mutation->getDisc()->getValue())
            ->put($mutation->getFile($locale), $json);
        $output = $this->app->make(StorageTranslations::class)(null, [
            'locale' => $locale,
        ]);
        if (!$expected instanceof Exception) {
            $this->assertEquals($expected, $output);
        }
    }

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return [
            'ok'        => [
                [
                    ['key' => 'ValueA', 'value' => 123],
                    ['key' => 'ValueB', 'value' => 'asd'],
                ],
                '[{"key": "ValueA","value": 123},{"key": "ValueB","value": "asd"}]',
                'en',
            ],
            'exception' => [
                StorageTranslationsFileCorrupted::class,
                '[{"key": "ValueA","value": 123}{"key": "ValueB","value": "asd"}]',
                'en',
            ],
        ];
    }
    // </editor-fold>

    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvokeQuery
     *
     * @param  array<mixed> $expected
     *
     * @param array<string,mixed> $input
    */
    public function testInvokeQuery(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        array $input = [],
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        if (!empty($input)) {
            $mutation = $this->app->make(UpdateApplicationStorageTranslations::class);
            Storage::fake($mutation->getDisc()->getValue())
                ->put($mutation->getFile('en'), json_encode($input));
        }

        // Test
        $this
        ->graphQL(/** @lang GraphQL */ '
            {
                application {
                    storage {
                        translations(locale:"en") {
                            key
                            value
                        }
                    }
                }
            }
        ')
        ->assertThat($expected);
    }
    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvokeQuery(): array {
        $input = [
            ['key' => 'ValueA', 'value' => '123'],
            ['key' => 'ValueB', 'value' => 'asd'],
        ];
        return (new CompositeDataProvider(
            new TenantDataProvider(),
            new AnyDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('application', StorageTranslations::class, [
                        'storage' => [
                            'translations' => $input,
                        ],
                    ]),
                    $input,
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
