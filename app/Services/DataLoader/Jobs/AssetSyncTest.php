<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\DataLoader\Commands\UpdateAsset;
use Illuminate\Contracts\Console\Kernel;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Jobs\AssetSync
 */
class AssetSyncTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param array<mixed> $expected
     */
    public function testInvoke(array $expected, string $assetId, ?bool $warrantyCheck, ?bool $withDocuments): void {
        $this->override(Kernel::class, static function (MockInterface $mock) use ($expected): void {
            $mock
                ->shouldReceive('call')
                ->with(UpdateAsset::class, $expected)
                ->once();
        });

        $this->app->make(AssetSync::class)
            ->init(
                id           : $assetId,
                warrantyCheck: $warrantyCheck,
                documents    : $withDocuments,
            )
            ->run();
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{string,?bool}>
     */
    public function dataProviderInvoke(): array {
        return [
            'asset only'                   => [
                [
                    'id' => '1cc137a2-61e5-4069-a407-f0e1f32dc634',
                ],
                '1cc137a2-61e5-4069-a407-f0e1f32dc634',
                null,
                null,
            ],
            'asset with documents'         => [
                [
                    'id'          => '21a50911-912b-4543-b721-51c7398e8384',
                    '--documents' => true,
                ],
                '21a50911-912b-4543-b721-51c7398e8384',
                null,
                true,
            ],
            'asset without documents'      => [
                [
                    'id'             => '21a50911-912b-4543-b721-51c7398e8384',
                    '--no-documents' => true,
                ],
                '21a50911-912b-4543-b721-51c7398e8384',
                null,
                false,
            ],
            'asset with warranty check'    => [
                [
                    'id'               => '29c0298a-14c8-4ca4-b7da-ef7ff71d19ae',
                    '--warranty-check' => true,
                ],
                '29c0298a-14c8-4ca4-b7da-ef7ff71d19ae',
                true,
                null,
            ],
            'asset without warranty check' => [
                [
                    'id'                  => '29c0298a-14c8-4ca4-b7da-ef7ff71d19ae',
                    '--no-warranty-check' => true,
                ],
                '29c0298a-14c8-4ca4-b7da-ef7ff71d19ae',
                false,
                null,
            ],
        ];
    }
    // </editor-fold>
}
