<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

use App\Services\Settings\Storage;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthRootDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Application\RecoverApplicationSettings
 */
class RecoverApplicationSettingsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

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
                    new GraphQLSuccess('recoverApplicationSettings', RecoverApplicationSettings::class, [
                        'result' => true,
                    ]),
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
