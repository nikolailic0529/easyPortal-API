<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\I18n\Translation\TranslationDefaults;
use App\Services\I18n\Translation\TranslationLoader;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\RootUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @deprecated Outdated
 *
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Application\Translations
 */
class TranslationsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvokeQuery
     */
    public function testInvokeQuery(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
            {
                application {
                    translations(locale: "de_DE") {
                        key
                        value
                        default
                    }
                }
            }
            ')
            ->assertThat($expected);
    }

    /**
     * @covers ::getTranslations
     */
    public function testGetTranslations(): void {
        $locale = $this->faker->locale();
        $loader = Mockery::mock(TranslationLoader::class);
        $loader
            ->shouldReceive('getTranslations')
            ->with($locale)
            ->once()
            ->andReturn([
                'a' => 'actual-a',
                'b' => 'actual-b',
            ]);
        $defaults = Mockery::mock(TranslationDefaults::class);
        $defaults
            ->shouldReceive('getTranslations')
            ->with($locale)
            ->once()
            ->andReturn([
                'a' => 'default-a',
                'c' => 'default-c',
            ]);

        $actual   = (new Translations($this->app, $loader, $defaults))->getTranslations($locale);
        $expected = [
            'a' => [
                'key'     => 'a',
                'value'   => 'actual-a',
                'default' => 'default-a',
            ],
            'b' => [
                'key'     => 'b',
                'value'   => 'actual-b',
                'default' => null,
            ],
            'c' => [
                'key'     => 'c',
                'value'   => 'default-c',
                'default' => 'default-c',
            ],
        ];

        self::assertEquals($expected, $actual);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvokeQuery(): array {
        return (new CompositeDataProvider(
            new RootOrganizationDataProvider('application'),
            new RootUserDataProvider('application'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('application', Translations::class),
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
