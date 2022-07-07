<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Client;

use App\Services\I18n\I18n;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
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
            $this->override(I18n::class, static function (MockInterface $mock) use ($translations): void {
                $mock
                    ->shouldReceive('getClientTranslations')
                    ->with('en')
                    ->andReturn($translations);
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
        return (new CompositeDataProvider(
            new AnyOrganizationDataProvider(),
            new AnyUserDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('client', Translations::class, [
                        'translations' => [
                            ['key' => 'ValueA', 'value' => '123'],
                            ['key' => 'ValueB', 'value' => 'asd'],
                        ],
                    ]),
                    [
                        'ValueA' => '123',
                        'ValueB' => 'asd',
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
