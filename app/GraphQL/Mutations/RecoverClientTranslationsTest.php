<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Filesystem\Storages\ClientTranslations;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery;
use Tests\DataProviders\GraphQL\RootDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\RecoverClientTranslations
 */
class RecoverClientTranslationsTest extends TestCase {
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
            $storage = Mockery::mock(ClientTranslations::class);
            $storage
                ->shouldReceive('delete')
                ->with(true)
                ->once()
                ->andReturn(true);

            $mutation = Mockery::mock(RecoverClientTranslations::class);
            $mutation->makePartial();
            $mutation->shouldAllowMockingProtectedMethods();
            $mutation
                ->shouldReceive('getStorage')
                ->once()
                ->andReturn($storage);

            $this->app->bind(RecoverClientTranslations::class, static function () use ($mutation) {
                return $mutation;
            });
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                mutation recoverClientTranslations {
                    recoverClientTranslations(input: {locale: "en"}) {
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
            new TenantDataProvider(),
            new RootDataProvider('recoverClientTranslations'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess(
                        'recoverClientTranslations',
                        RecoverClientTranslations::class,
                        [
                            'result' => true,
                        ],
                    ),
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}