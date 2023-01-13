<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

use App\Services\Settings\Storage;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthRootDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\Application\RecoverApplicationSettings
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class RecoverApplicationSettingsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

        // Mock
        if ($expected instanceof GraphQLSuccess) {
            $this->override(Storage::class, static function (MockInterface $mock): void {
                $mock
                    ->shouldReceive('delete')
                    ->once()
                    ->andReturn(true);
            });
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                mutation recoverApplicationSettings {
                    recoverApplicationSettings {
                        result
                    }
                }')
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new AuthOrgRootDataProvider('recoverApplicationSettings'),
            new AuthRootDataProvider('recoverApplicationSettings'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('recoverApplicationSettings', [
                        'result' => true,
                    ]),
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
