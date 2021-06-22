<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

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
 * @coversDefaultClass \App\GraphQL\Queries\Application\Permissions
 */
class PermissionsTest extends TestCase {
    /**
     * @dataProvider dataProviderQuery
     */
    public function testQuery(
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
                  application{
                    permissions
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
            new AnyOrganizationDataProvider('application'),
            new AnyUserDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('application', Permissions::class),
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
