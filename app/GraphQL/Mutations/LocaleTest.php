<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\AnyDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function json_encode;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Locale
 */
class LocaleTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(Response $expected, Closure $tenantFactory, Closure $userFactory = null): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ 'mutation Locale($locale: String!) {
                locale(locale: $locale)
            }', [ 'locale' => 'en_UK'])
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            $this->assertEquals('en_UK', $this->app->getLocale());
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new TenantDataProvider(),
            new AnyDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('locale', Locale::class, json_encode(true)),
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
