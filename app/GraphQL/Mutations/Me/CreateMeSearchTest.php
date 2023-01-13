<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Me;

use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthMeDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\Me\CreateMeSearch
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class CreateMeSearchTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     * @param array<string,mixed> $data
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        array $data = [
            'name'       => '',
            'key'        => '',
            'conditions' => '',
        ],
    ): void {
        // Prepare
        $user = $this->setUser($userFactory, $this->setOrganization($orgFactory));

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

            self::assertIsArray($created);
            self::assertNotNull($created['id']);
            self::assertNotNull($created['created_at']);
            self::assertEquals($data['key'], $created['key']);
            self::assertEquals($data['name'], $created['name']);
            self::assertEquals($data['conditions'], $created['conditions']);
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
            new AuthOrgDataProvider('createMeSearch'),
            new AuthMeDataProvider('createMeSearch'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('createMeSearch'),
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
