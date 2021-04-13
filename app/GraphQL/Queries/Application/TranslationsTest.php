<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use Closure;
use Exception;
use Illuminate\Support\Facades\Storage;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\RootDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function is_array;
/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Application\Translations
*/
class TranslationsTest extends TestCase {
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
        $query = $this->app->make(Translations::class);
        Storage::fake($query->getDisc()->getValue())
            ->put($query->getFile($locale), $json);
        $output = $this->app->make(Translations::class)(null, [
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
                '{"ValueA":123,"ValueB":"asd"}',
                'de',
            ],
            'exception' => [
                TranslationsFileCorrupted::class,
                '{"ValueA":123,"ValueB":"asd",}',
                'de',
            ],
        ];
    }
    // </editor-fold>

    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvokeQuery
     *
     * @param  array<mixed> $expected
    */
    public function testInvokeQuery(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        string $json = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        if ($json) {
            $query = $this->app->make(Translations::class);
            Storage::fake($query->getDisc()->getValue())
                ->put($query->getFile('de'), $json);
        }

        // Test
        $this
        ->graphQL(/** @lang GraphQL */ '
            {
                application {
                    translations(locale:"de") {
                        key
                        value
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
        return (new CompositeDataProvider(
            new TenantDataProvider(),
            new RootDataProvider('application'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('application', Translations::class, [
                        'translations' => [
                            ['key' => 'ValueA', 'value' => '123'],
                            ['key' => 'ValueB', 'value' => 'asd'],
                        ],
                    ]),
                    '{"ValueA":"123","ValueB":"asd"}',
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
