<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthRootDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @deprecated Please {@see \App\GraphQL\Mutations\Locale\Reset}
 *
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Application\RecoverApplicationTranslations
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
        Closure $organizationFactory,
        Closure $userFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

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
            new AuthOrgRootDataProvider('recoverApplicationTranslations'),
            new AuthRootDataProvider('recoverApplicationTranslations'),
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
