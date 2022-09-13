<?php declare(strict_types = 1);

namespace App\Models;

use Tests\Providers\ModelsProvider;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Models\Reseller
 */
class ResellerTest extends TestCase {
    public function testDelete(): void {
        $models = (new ModelsProvider())($this);
        $model  = $models['reseller'] ?? null;

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
                'organization'                  => true,
                'organizationRole'              => true,
                'organizationRolePermission'    => true,
                'user'                          => false,
                'userSearch'                    => false,
                'userInvitation'                => false,
                'organizationUser'              => true,
                'organizationChangeRequest'     => true,
                'organizationChangeRequestFile' => true,
                'reseller'                      => true,
                'resellerKpi'                   => true,
                'resellerCustomerKpi'           => true,
                'resellerContact'               => true,
                'resellerContactType'           => true,
                'resellerStatus'                => true,
                'resellerLocation'              => true,
                'resellerLocationType'          => true,
                'resellerChangeRequest'         => false,   // no relation yet
                'resellerChangeRequestFile'     => false,   // no relation yet
                'resellerCustomer'              => true,
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
                'assetWarranty'                 => false,
                'assetWarrantyServiceLevel'     => false,
                'quoteRequest'                  => false,
                'quoteRequestAsset'             => false,
                'quoteRequestContact'           => false,
                'quoteRequestDuration'          => false,
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
}
