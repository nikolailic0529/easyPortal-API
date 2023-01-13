<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Client;

use App\Services\I18n\I18n;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\UnknownOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\UnknownUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\GraphQL\Queries\Client\Translations
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class TranslationsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     * @param array<string,mixed> $translations
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        array $translations = [],
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

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
            new UnknownOrgDataProvider(),
            new UnknownUserDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('client', [
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
