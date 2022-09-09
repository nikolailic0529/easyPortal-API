<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Data;

use App\Models\Data\Oem;
use App\Models\Data\Product;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
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
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class ProductsTest extends TestCase {
    use WithQueryLog;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     * @coversNothing
     *
     * @param OrganizationFactory                        $orgFactory
     * @param UserFactory                                $userFactory
     * @param Closure(static): array<string, mixed>|null $prepare
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $prepare = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

        $where   = $prepare ? $prepare($this) : null;
        $queries = $this->getQueryLog()->flush();

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                query products($where: SearchByConditionProductsQuery) {
                    products(where: $where) {
                        id
                        sku
                        name
                        oem_id
                        oem {
                            id
                            key
                        }
                    }
                }
                GRAPHQL,
                [
                    'where' => $where,
                ],
            )
            ->assertThat($expected);

        // Eager Loading
        if ($expected instanceof GraphQLSuccess) {
            self::assertCount(4, $queries);
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
            new AuthOrgDataProvider('products'),
            new AuthMeDataProvider('products'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('products', [
                        [
                            'id'     => 'e920855a-064c-4cd4-9306-efb7bc13b3d4',
                            'sku'    => 'Product#A',
                            'name'   => 'Product A',
                            'oem_id' => '1d534c00-66f5-41c6-acbe-8d094a814404',
                            'oem'    => [
                                'id'  => '1d534c00-66f5-41c6-acbe-8d094a814404',
                                'key' => 'Oem#1',
                            ],
                        ],
                    ]),
                    static function (): array {
                        $oem = Oem::factory()->create([
                            'id'  => '1d534c00-66f5-41c6-acbe-8d094a814404',
                            'key' => 'Oem#1',
                        ]);

                        Product::factory()->create([
                            'id'     => 'e920855a-064c-4cd4-9306-efb7bc13b3d4',
                            'sku'    => 'Product#A',
                            'name'   => 'Product A',
                            'oem_id' => $oem,
                        ]);
                        Product::factory()->create([
                            'id'   => 'b59041d2-5fc2-4712-9860-0995d17b3cf4',
                            'sku'  => 'Product#B',
                            'name' => 'Product B',
                        ]);

                        return [
                            'oem_id' => [
                                'equal' => $oem->getKey(),
                            ],
                        ];
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
