<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

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
 * @covers \App\GraphQL\Mutations\Application\SetApplicationLocale
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class SetApplicationLocaleTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testInvoke(Response $expected, mixed $orgFactory, mixed $userFactory = null): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

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
            new UnknownOrgDataProvider(),
            new UnknownUserDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('setApplicationLocale', [
                        'result' => true,
                    ]),
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
