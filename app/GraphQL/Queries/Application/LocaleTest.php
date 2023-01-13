<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\I18n\CurrentLocale;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\UnknownOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\UnknownUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\GraphQL\Queries\Application\Locale
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class LocaleTest extends TestCase {
    /**
     * @dataProvider dataProviderQuery
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testQuery(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        string $locale = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

        if ($locale) {
            $this->app->make(CurrentLocale::class)->set($locale);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                {
                  application{
                    locale
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
    public function dataProviderQuery(): array {
        return (new CompositeDataProvider(
            new UnknownOrgDataProvider(),
            new UnknownUserDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('application', [
                        'locale' => 'fr',
                    ]),
                    'fr',
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
