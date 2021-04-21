<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\UserDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\CreateMeSearch
 */
class CreateMeSearchTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     *
     * @param array<string,mixed> $data
     */
    public function testInvoke(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        array $data = [
            'name'       => '',
            'key'        => '',
            'conditions' => '',
        ],
    ): void {
        // Prepare
        $user = $this->setUser($userFactory, $this->setTenant($tenantFactory));

        if ($user) {
            $user->save();
        }

        // Test
        $this
        ->graphQL(/** @lang GraphQL */ 'mutation CreateMeSearch($input: CreateMeSearchInput!) {
            createMeSearch(input:$input) {
                created {
                    name
                    key
                    conditions
                }
            }
        }', [ 'input' => $data ])
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
            new UserDataProvider('createMeSearch'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('createMeSearch', CreateMeSearch::class, [
                        'created' => [
                            'conditions' => 'conditionsObject',
                            'key'        => 'key1',
                            'name'       => 'name aa',
                        ],
                    ]),
                    [
                        'conditions' => 'conditionsObject',
                        'key'        => 'key1',
                        'name'       => 'name aa',
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
