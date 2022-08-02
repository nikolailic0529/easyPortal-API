<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Models\Oem;
use App\Models\Organization;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Routing\UrlGenerator;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\NotFound;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\Constraints\Attachments\XlsxAttachment;
use Tests\DataProviders\Http\Organizations\AuthOrgRootDataProvider;
use Tests\DataProviders\Http\Users\OrgUserDataProvider;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithSettings;
use Tests\WithUser;

/**
 * @internal
 * @coversDefaultClass \App\Http\Controllers\FilesController
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class OemsControllerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory                             $orgFactory
     * @param UserFactory                                     $userFactory
     * @param Closure(static, ?Organization, ?User): Oem|null $prepare
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $prepare = null,
    ): void {
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
                    static function (): Oem {
                        $oem    = Oem::factory()->create(['name' => 'Test OEM']);
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
                    static function (): Oem {
                        return Oem::factory()->make();
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
