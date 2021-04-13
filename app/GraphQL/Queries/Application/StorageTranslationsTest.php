<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\Filesystem\Disks\UIDisk;
use App\Services\Filesystem\Storages\UITranslations;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\AnyDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Application\StorageTranslations
 */
class StorageTranslationsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     *
     * @param array<string,mixed> $translations
     */
    public function testInvoke(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        array $translations = [],
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
    public function dataProviderInvoke(): array {
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
