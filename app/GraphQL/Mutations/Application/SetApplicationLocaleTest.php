<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

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
 * @coversDefaultClass \App\GraphQL\Mutations\Application\SetApplicationLocale
 */
class SetApplicationLocaleTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(Response $expected, Closure $organizationFactory, Closure $userFactory = null): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ 'mutation setApplicationLocale($input: SetApplicationLocaleInput!) {
                setApplicationLocale(input: $input){
                    result
                }
            }', ['input' => ['locale' => 'en_BB']])
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            self::assertEquals('en_BB', $this->app->getLocale());
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
            new AnyOrganizationDataProvider('setApplicationLocale'),
            new AnyUserDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('setApplicationLocale', SetApplicationLocale::class, [
                        'result' => true,
                    ]),
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
