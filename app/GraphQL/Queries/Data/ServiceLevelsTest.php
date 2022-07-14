<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Data;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use Closure;
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
 * @coversNothing
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class ServiceLevelsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $translationsFactory = null,
        Closure $factory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));
        $this->setTranslations($translationsFactory);

        if ($factory) {
            $factory($this);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                serviceLevels(where: {documentEntries: { where: {}, count: {lessThan: 1} }}) {
                    id
                    oem_id
                    service_group_id
                    sku
                    name
                    description
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
            new AuthOrgDataProvider('serviceLevels'),
            new AuthMeDataProvider('serviceLevels'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('serviceLevels', [
                        [
                            'id'               => '152d295f-e888-44a2-bdf3-7dc986c4524c',
                            'oem_id'           => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'service_group_id' => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                            'sku'              => 'b',
                            'name'             => 'Translated (fallback)',
                            'description'      => 'Description (fallback)',
                        ],
                        [
                            'id'               => 'd502e8e9-a59f-4475-8fa8-24a0dc4049a2',
                            'oem_id'           => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'service_group_id' => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                            'sku'              => 'c',
                            'name'             => 'Level',
                            'description'      => 'Description',
                        ],
                        [
                            'id'               => 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0',
                            'oem_id'           => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'service_group_id' => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                            'sku'              => 'a',
                            'name'             => 'Translated (locale)',
                            'description'      => 'Description (locale)',
                        ],
                    ]),
                    static function (TestCase $test, string $locale): array {
                        $a     = 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0';
                        $b     = '152d295f-e888-44a2-bdf3-7dc986c4524c';
                        $model = (new ServiceLevel())->getMorphClass();

                        return [
                            $locale => [
                                "models.{$model}.{$a}.name"        => 'Translated (locale)',
                                "models.{$model}.{$b}.name"        => 'Translated (fallback)',
                                "models.{$model}.{$a}.description" => 'Description (locale)',
                                "models.{$model}.{$b}.description" => 'Description (fallback)',
                            ],
                        ];
                    },
                    static function (): void {
                        $oem   = Oem::factory()->create([
                            'id'  => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'key' => 'oem',
                        ]);
                        $group = ServiceGroup::factory()->create([
                            'id'     => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                            'oem_id' => $oem,
                            'sku'    => 'group',
                            'name'   => 'Group',
                        ]);

                        ServiceLevel::factory()->create([
                            'id'               => 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0',
                            'oem_id'           => $oem,
                            'service_group_id' => $group,
                            'sku'              => 'a',
                            'name'             => 'Level (Should be translated)',
                            'description'      => 'Description (Should be translated)',
                        ]);

                        ServiceLevel::factory()->create([
                            'id'               => '152d295f-e888-44a2-bdf3-7dc986c4524c',
                            'oem_id'           => $oem,
                            'service_group_id' => $group,
                            'sku'              => 'b',
                            'name'             => 'Level (Should be translated via fallback)',
                            'description'      => 'Description (Should be translated via fallback)',
                        ]);

                        ServiceLevel::factory()->create([
                            'id'               => 'd502e8e9-a59f-4475-8fa8-24a0dc4049a2',
                            'oem_id'           => $oem,
                            'service_group_id' => $group,
                            'sku'              => 'c',
                            'name'             => 'Level',
                            'description'      => 'Description',
                        ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
