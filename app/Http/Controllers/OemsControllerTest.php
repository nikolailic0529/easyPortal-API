<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Models\Data\Oem;
use App\Models\Data\ServiceGroup;
use App\Models\Data\ServiceLevel;
use App\Models\Organization;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Routing\UrlGenerator;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\NotFound;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Utils\WithTranslations;
use Tests\Constraints\Attachments\XlsxAttachment;
use Tests\DataProviders\Http\Organizations\AuthOrgRootDataProvider;
use Tests\DataProviders\Http\Users\OrgUserDataProvider;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\Http\Controllers\OemsController
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type TranslationsFactory from WithTranslations
 */
class OemsControllerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory                             $orgFactory
     * @param UserFactory                                     $userFactory
     * @param TranslationsFactory                             $translationsFactory
     * @param Closure(static, ?Organization, ?User): Oem|null $prepare
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $translationsFactory = null,
        Closure $prepare = null,
    ): void {
        $this->setTranslations($translationsFactory);

        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);
        $oem  = $prepare
            ? $prepare($this, $org, $user)
            : Oem::factory()->create();
        $url  = $this->app->make(UrlGenerator::class)->route('oem', [
            'oem' => $oem->getKey(),
        ]);

        $this->get($url)->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new AuthOrgRootDataProvider(),
            new OrgUserDataProvider([
                'administer',
            ]),
            new ArrayDataProvider([
                'ok'          => [
                    new XlsxAttachment('Test OEM.xlsx', $this->getTestData()->file('.csv')),
                    static function (TestCase $test, string $locale): array {
                        $key   = '240442d1-4387-4201-9d9b-39d7b70d1ef4';
                        $model = (new ServiceLevel())->getMorphClass();

                        return [
                            'de_DE' => [
                                "models.{$model}.{$key}.name"        => '(german) Group A / Level A',
                                "models.{$model}.{$key}.description" => '(german) Group A / Level A Description',
                            ],
                            'it_IT' => [
                                "models.{$model}.{$key}.name"        => '(italian) Group A / Level A',
                                "models.{$model}.{$key}.description" => '(italian) Group A / Level A Description',
                            ],
                        ];
                    },
                    static function (): Oem {
                        $oem    = Oem::factory()->create([
                            'key'  => 'TestOEM',
                            'name' => 'Test OEM',
                        ]);
                        $groupA = ServiceGroup::factory()->create([
                            'sku'    => 'A',
                            'name'   => 'Group A',
                            'oem_id' => $oem,
                        ]);
                        $groupB = ServiceGroup::factory()->create([
                            'sku'    => 'B',
                            'name'   => 'Group B',
                            'oem_id' => $oem,
                        ]);

                        ServiceGroup::factory()->create([
                            'sku'  => 'C',
                            'name' => 'Group C',
                        ]);

                        ServiceLevel::factory()->create([
                            'id'               => '240442d1-4387-4201-9d9b-39d7b70d1ef4',
                            'sku'              => 'A',
                            'name'             => 'Group A / Level A',
                            'description'      => 'Group A / Level A Description',
                            'oem_id'           => $oem,
                            'service_group_id' => $groupA,
                        ]);

                        ServiceLevel::factory()->create([
                            'sku'              => 'B',
                            'name'             => 'Group A / Level B',
                            'description'      => 'Group A / Level B Description',
                            'oem_id'           => $oem,
                            'service_group_id' => $groupA,
                        ]);

                        ServiceLevel::factory()->create([
                            'sku'              => 'A',
                            'name'             => 'Group B / Level A',
                            'description'      => 'Group B / Level A Description',
                            'oem_id'           => $oem,
                            'service_group_id' => $groupB,
                        ]);

                        return $oem;
                    },
                ],
                'unknown oem' => [
                    new NotFound(),
                    static function (): array {
                        return [
                            // empty
                        ];
                    },
                    static function (): Oem {
                        return Oem::factory()->make();
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
