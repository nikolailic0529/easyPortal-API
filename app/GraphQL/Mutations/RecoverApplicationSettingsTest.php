<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Settings\Storage;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\RootUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\RecoverApplicationSettings
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
            $storage = Mockery::mock(Storage::class);
            $storage
                ->shouldReceive('delete')
                ->with(true)
                ->once()
                ->andReturn(true);

            $this->app->bind(Storage::class, static function () use ($storage): Storage {
                return $storage;
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
            new OrganizationDataProvider('recoverApplicationSettings'),
            new RootUserDataProvider('recoverApplicationSettings'),
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
