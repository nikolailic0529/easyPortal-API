<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Tenants\TenantDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
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
        $response = $this
            ->graphQL(/** @lang GraphQL */ 'mutation CreateMeSearch($input: CreateMeSearchInput!) {
                createMeSearch(input:$input) {
                    created {
                        id
                        key
                        name
                        conditions
                        created_at
                    }
                }
            }', ['input' => $data])
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            $created = $response->json('data.createMeSearch.created');

            $this->assertIsArray($created);
            $this->assertNotNull($created['id']);
            $this->assertNotNull($created['created_at']);
            $this->assertEquals($data['key'], $created['key']);
            $this->assertEquals($data['name'], $created['name']);
            $this->assertEquals($data['conditions'], $created['conditions']);
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
            new TenantDataProvider(),
            new UserDataProvider('createMeSearch'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('createMeSearch', CreateMeSearch::class),
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
