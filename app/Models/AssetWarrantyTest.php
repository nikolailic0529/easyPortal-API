<?php declare(strict_types = 1);

namespace App\Models;

use Tests\Providers\ModelsProvider;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Models\AssetWarranty
 */
class AssetWarrantyTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    public function testDelete(): void {
        $models = (new ModelsProvider())($this);
        $model  = $models['assetWarranty'] ?? null;

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
                'asset'                         => false,
                'assetContact'                  => false,
                'assetContactType'              => false,
                'assetCoverage'                 => false,
                'assetTag'                      => false,
                'assetChangeRequest'            => false,
                'assetChangeRequestFile'        => false,
                'assetWarranty'                 => true,
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
     * @dataProvider dataProviderIsExtended
     *
     * @param array<string,mixed> $properties
     */
    public function testIsExtended(bool $expected, array $properties): void {
        self::assertEquals($expected, AssetWarranty::factory()->make($properties)->isExtended());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{bool,array<string,mixed>}>
     */
    public function dataProviderIsExtended(): array {
        return [
            'normal warranty'   => [
                false,
                [
                    'document_number' => null,
                ],
            ],
            'extended warranty' => [
                true,
                [
                    'document_number' => 123,
                ],
            ],
        ];
    }
    // </editor-fold>
}
