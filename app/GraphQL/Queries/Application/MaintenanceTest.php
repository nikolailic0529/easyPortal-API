<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\Maintenance\Storage;
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
 * @covers \App\GraphQL\Queries\Application\Maintenance
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class MaintenanceTest extends TestCase {
    /**
     * @dataProvider dataProviderQuery
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     * @param array<mixed>        $data
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        array $data = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

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
            new UnknownOrgDataProvider(),
            new UnknownUserDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('application', [
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
