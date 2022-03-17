<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Client;

use App\Services\Filesystem\Disks\ClientDisk;
use App\Services\I18n\Storages\ClientTranslations;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AnyOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\AnyUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Client\Translations
 */
class TranslationsTest extends TestCase {
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
        Closure $organizationFactory,
        Closure $userFactory = null,
        array $translations = [],
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

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
            ->graphQL(/** @lang GraphQL */ '
            {
                client {
                    translations(locale:"en") {
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
    public function dataProviderInvoke(): array {
        $input = [
            ['key' => 'ValueA', 'value' => '123'],
            ['key' => 'ValueB', 'value' => 'asd'],
        ];

        return (new CompositeDataProvider(
            new AnyOrganizationDataProvider(),
            new AnyUserDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('client', Translations::class, [
                        'translations' => $input,
                    ]),
                    $input,
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
