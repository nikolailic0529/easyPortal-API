<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\Maintenance\Storage;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\UnknownOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\AnyUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Application\Maintenance
 */
class MaintenanceTest extends TestCase {
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderQuery
     *
     * @param array<mixed> $data
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        array $data = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        if ($data) {
            $this->app->make(Storage::class)->save($data);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                {
                    application {
                        maintenance {
                            enabled
                            message
                            start
                            end
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
    public function dataProviderQuery(): array {
        return (new CompositeDataProvider(
            new UnknownOrganizationDataProvider(),
            new AnyUserDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('application', self::class, [
                        'maintenance' => [
                            'enabled' => true,
                            'message' => 'abc',
                            'start'   => '2021-10-19T10:25:00+00:00',
                            'end'     => '2021-10-19T10:55:00+00:00',
                        ],
                    ]),
                    [
                        'enabled' => true,
                        'message' => 'abc',
                        'start'   => '2021-10-19T10:25:00+00:00',
                        'end'     => '2021-10-19T10:55:00+00:00',
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
