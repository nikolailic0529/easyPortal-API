<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Services\LocaleService;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Tenants\AnyTenantDataProvider;
use Tests\DataProviders\GraphQL\Users\AnyUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function json_encode;

/**
 * @internal
 * @coversNothing
 */
class LocaleTest extends TestCase {
    /**
     * @dataProvider dataProviderQuery
     */
    public function testQuery(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        string $locale = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        if ($locale) {
            $this->app->make(LocaleService::class)->set($locale);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                {
                    locale
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
            new AnyTenantDataProvider('locale'),
            new AnyUserDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('locale', Locale::class, json_encode('fr')),
                    'fr',
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
