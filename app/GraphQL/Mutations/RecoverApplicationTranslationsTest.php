<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Filesystem\Storages\AppTranslations;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery;
use Tests\DataProviders\GraphQL\Tenants\TenantDataProvider;
use Tests\DataProviders\GraphQL\Users\RootUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\RecoverApplicationTranslations
 */
class RecoverApplicationTranslationsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        // Mock
        if ($expected instanceof GraphQLSuccess) {
            $storage = Mockery::mock(AppTranslations::class);
            $storage
                ->shouldReceive('delete')
                ->with(true)
                ->once()
                ->andReturn(true);

            $mutation = Mockery::mock(RecoverApplicationTranslations::class);
            $mutation->makePartial();
            $mutation->shouldAllowMockingProtectedMethods();
            $mutation
                ->shouldReceive('getStorage')
                ->once()
                ->andReturn($storage);

            $this->app->bind(RecoverApplicationTranslations::class, static function () use ($mutation) {
                return $mutation;
            });
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                mutation recoverApplicationTranslations {
                    recoverApplicationTranslations(input: {locale: "en"}) {
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
            new TenantDataProvider('recoverApplicationTranslations'),
            new RootUserDataProvider('recoverApplicationTranslations'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('recoverApplicationTranslations', RecoverApplicationTranslations::class, [
                        'result' => true,
                    ]),
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
