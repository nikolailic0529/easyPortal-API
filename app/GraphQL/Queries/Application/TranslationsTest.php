<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\Filesystem\Disks\AppDisk;
use App\Services\Filesystem\Storages\AppTranslations;
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
 * @coversDefaultClass \App\GraphQL\Queries\Application\Translations
 */
class TranslationsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvokeQuery
     *
     * @param array<mixed> $translations
     */
    public function testInvokeQuery(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        array $translations = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        if ($translations) {
            $disk    = $this->app()->make(AppDisk::class);
            $storage = new AppTranslations($disk, 'de');

            $storage->save($translations);

            $this->app->bind(AppDisk::class, static function () use ($disk): AppDisk {
                return $disk;
            });
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
            {
                application {
                    translations(locale: "de") {
                        key
                        value
                    }
                }
            }
        ')
            ->assertThat($expected);
    }
    // </editor-fold>

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
                    [
                        'ValueA' => 123,
                        'ValueB' => 'asd',
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
