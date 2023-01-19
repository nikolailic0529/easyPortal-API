<?php declare(strict_types = 1);

namespace App\Models;

use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;
use Tests\Providers\ModelsProvider;
use Tests\TestCase;
use Tests\WithSettings;

/**
 * @internal
 * @covers \App\Models\Asset
 *
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class AssetTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    public function testDelete(): void {
        $models = (new ModelsProvider())($this);
        $model  = $models['asset'] ?? null;

        self::assertNotNull($model);
        self::assertModelHasAllRelations($model);

        $model->delete();

        self::assertModelsTrashed(
            [
                'distributor'                   => false,
                'type'                          => false,
                'status'                        => false,
                'coverage'                      => false,
                'country'                       => false,
                'city'                          => false,
                'currency'                      => false,
                'language'                      => false,
                'permission'                    => false,
                'psp'                           => false,
                'tag'                           => false,
                'team'                          => false,
                'oem'                           => false,
                'product'                       => false,
                'productLine'                   => false,
                'productGroup'                  => false,
                'serviceGroup'                  => false,
                'serviceLevel'                  => false,
                'oemGroup'                      => false,
                'location'                      => false,
                'locationReseller'              => false,
                'locationCustomer'              => false,
                'organization'                  => false,
                'organizationRole'              => false,
                'organizationRolePermission'    => false,
                'organizationUser'              => false,
                'organizationChangeRequest'     => false,
                'organizationChangeRequestFile' => false,
                'user'                          => false,
                'userSearch'                    => false,
                'userInvitation'                => false,
                'reseller'                      => false,
                'resellerKpi'                   => false,
                'resellerCustomerKpi'           => false,
                'resellerContact'               => false,
                'resellerContactType'           => false,
                'resellerStatus'                => false,
                'resellerLocation'              => false,
                'resellerLocationType'          => false,
                'resellerChangeRequest'         => false,
                'resellerChangeRequestFile'     => false,
                'resellerCustomer'              => false,
                'customer'                      => false,
                'customerKpi'                   => false,
                'customerContact'               => false,
                'customerContactType'           => false,
                'customerStatus'                => false,
                'customerLocation'              => false,
                'customerLocationType'          => false,
                'customerChangeRequest'         => false,
                'customerChangeRequestFile'     => false,
                'audit'                         => false,
                'asset'                         => true,
                'assetContact'                  => false,
                'assetContactType'              => false,
                'assetCoverage'                 => false,
                'assetTag'                      => false,
                'assetChangeRequest'            => false,
                'assetChangeRequestFile'        => false,
                'assetWarranty'                 => false,
                'quoteRequest'                  => false,
                'quoteRequestAsset'             => false,
                'quoteRequestContact'           => false,
                'quoteRequestContactType'       => false,
                'quoteRequestDocument'          => false,
                'quoteRequestDuration'          => false,
                'quoteRequestFile'              => false,
                'contract'                      => false,
                'contractStatus'                => false,
                'contractEntry'                 => false,
                'contractContact'               => false,
                'contractContactType'           => false,
                'contractChangeRequest'         => false,
                'contractChangeRequestFile'     => false,
                'contractNote'                  => false,
                'contractNoteFile'              => false,
                'quote'                         => false,
                'quoteStatus'                   => false,
                'quoteEntry'                    => false,
                'quoteContact'                  => false,
                'quoteContactType'              => false,
                'quoteChangeRequest'            => false,
                'quoteChangeRequestFile'        => false,
                'quoteNote'                     => false,
                'quoteNoteFile'                 => false,
            ],
            $models,
        );
    }

    /**
     * @dataProvider dataProviderGetLastWarranty
     *
     * @param Closure(static): Collection<array-key, AssetWarranty> $warrantiesFactory
     * @param SettingsFactory                                       $settingsFactory
     */
    public function testGetLastWarranty(
        ?string $expected,
        mixed $settingsFactory,
        Closure $warrantiesFactory,
    ): void {
        $this->setRootOrganization($this->setOrganization(Organization::factory()->make()));
        $this->setSettings($settingsFactory);

        $warranties = $warrantiesFactory($this);
        $warranty   = Asset::getLastWarranty($warranties);

        self::assertEquals($expected, $warranty?->getKey());
    }
    //</editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<?string, array{?string, SettingsFactory, Closure(static): Collection<array-key, AssetWarranty>}>
     */
    public function dataProviderGetLastWarranty(): array {
        return [
            'normal' => [
                'b4eba591-ca8d-4366-9c3a-7c1009d70c75',
                null,
                static function (): Collection {
                    $date       = Date::now();
                    $warranties = Collection::make([
                        AssetWarranty::factory()->make([
                            'id'  => 'b4eba591-ca8d-4366-9c3a-7c1009d70c75',
                            'end' => $date,
                        ]),
                        AssetWarranty::factory()->make([
                            'id'  => 'c17c7440-b598-4c86-966c-e324876cf600',
                            'end' => $date,
                        ]),
                        AssetWarranty::factory()->make([
                            'id'  => '36d90c00-6852-423e-870c-ab0c8bfe515f',
                            'end' => $date->subDay(),
                        ]),
                    ]);

                    return $warranties;
                },
            ],
            'mixed'  => [
                '2975b9a3-f0dd-48e2-a402-565877d3fed7',
                null,
                static function (): Collection {
                    $date       = Date::now();
                    $hidden     = Document::factory()->create([
                        'is_hidden'   => true,
                        'is_contract' => true,
                        'is_quote'    => false,
                    ]);
                    $contract   = Document::factory()->create([
                        'is_hidden'   => false,
                        'is_contract' => true,
                        'is_quote'    => false,
                    ]);
                    $document   = Document::factory()->create();
                    $warranties = Collection::make([
                        AssetWarranty::factory()->create([
                            'id'              => 'b4eba591-ca8d-4366-9c3a-7c1009d70c75',
                            'end'             => $date,
                            'document_id'     => $hidden,
                            'document_number' => $hidden->number,
                        ]),
                        AssetWarranty::factory()->create([
                            'id'              => 'c17c7440-b598-4c86-966c-e324876cf600',
                            'end'             => $date,
                            'document_id'     => $document,
                            'document_number' => $document->number,
                        ]),
                        AssetWarranty::factory()->create([
                            'id'              => '36d90c00-6852-423e-870c-ab0c8bfe515f',
                            'end'             => $date->subDay(),
                            'document_id'     => $contract,
                            'document_number' => $contract->number,
                        ]),
                        AssetWarranty::factory()->create([
                            'id'              => '2975b9a3-f0dd-48e2-a402-565877d3fed7',
                            'end'             => $date->subDay(),
                            'document_id'     => $contract,
                            'document_number' => $contract->number,
                        ]),
                    ]);

                    return $warranties;
                },
            ],
        ];
    }
    // </editor-fold>
}
