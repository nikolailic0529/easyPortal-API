<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Tenants\TenantDataProvider;
use Tests\DataProviders\GraphQL\Users\RootUserDataProvider;
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
     */
    public function testInvokeQuery(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

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
            new TenantDataProvider('application'),
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
