<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Data\Location;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\Reseller;
use App\Services\DataLoader\Collector\Data;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\DataLoader\Processors\Importer\ImporterCollectedData
 */
class ImporterCollectedDataTest extends TestCase {
    public function testCollect(): void {
        // Prepare
        $threshold = 2;
        $collected = new ImporterCollectedData();
        $model     = Customer::factory()->make();
        $dataA     = (new Data())
            ->addAll(Asset::class, [
                '5a5c0d8b-e95e-449b-bf16-ab303b4fcabf',
                '626ce822-de06-44b6-8273-66060b948d9b',
            ])
            ->addAll(Customer::class, [
                '2d4c096c-e4dc-40c9-aa21-c34f7f686d54',
                'd7676f89-0027-4a58-8be5-41d50d4b9192',
            ])
            ->collectObjectChange($model);
        $dataB     = (new Data())
            ->addAll(Asset::class, [
                '606576ec-5cd6-4144-bc25-c8641bbe78ad',
                'be8e075d-bd24-4638-b9d7-786268b8af52',
            ])
            ->addAll(Customer::class, [
                '26e34a7c-5f62-4684-aa63-1cb2250e7fec',
                '0d82c039-7471-4138-8b7e-f5f1fa49b0e4',
            ])
            ->collectObjectChange($model);
        $dataC     = (new Data())
            ->addAll(Asset::class, [
                '12b9ebea-0320-40dc-8783-f4aed654c84c',
                '8d0fd5e9-2e89-4d6e-84f2-ca070c5c169b',
            ])
            ->addAll(Customer::class, [
                'e7704b8d-0e74-4074-812a-eb69255f977c',
                '090fdf41-28ff-4f00-aede-8bdd8e246149',
            ]);

        // No Data
        self::assertNull($collected->collect($threshold, $dataA));

        // Same
        self::assertNull($collected->collect($threshold, $dataA));
        self::assertNull($collected->collect($threshold, $dataA));
        self::assertNull($collected->collect($threshold, $dataA));

        // Unchanged
        self::assertNull($collected->collect($threshold, $dataC));

        // Collect
        self::assertEquals(
            [
                Reseller::class    => [
                    // empty
                ],
                Customer::class    => [
                    '2d4c096c-e4dc-40c9-aa21-c34f7f686d54' => '2d4c096c-e4dc-40c9-aa21-c34f7f686d54',
                    'd7676f89-0027-4a58-8be5-41d50d4b9192' => 'd7676f89-0027-4a58-8be5-41d50d4b9192',
                ],
                Asset::class       => [
                    '5a5c0d8b-e95e-449b-bf16-ab303b4fcabf' => '5a5c0d8b-e95e-449b-bf16-ab303b4fcabf',
                    '626ce822-de06-44b6-8273-66060b948d9b' => '626ce822-de06-44b6-8273-66060b948d9b',
                ],
                Document::class    => [
                    // empty
                ],
                Distributor::class => [
                    // empty
                ],
                Location::class    => [
                    // empty
                ],
            ],
            $collected->collect($threshold, $dataB)?->getData(),
        );

        // Data
        self::assertEquals(
            [
                Reseller::class    => [
                    // empty
                ],
                Customer::class    => [
                    '26e34a7c-5f62-4684-aa63-1cb2250e7fec' => '26e34a7c-5f62-4684-aa63-1cb2250e7fec',
                    '0d82c039-7471-4138-8b7e-f5f1fa49b0e4' => '0d82c039-7471-4138-8b7e-f5f1fa49b0e4',
                    $model->getKey()                       => $model->getKey(),
                ],
                Asset::class       => [
                    '606576ec-5cd6-4144-bc25-c8641bbe78ad' => '606576ec-5cd6-4144-bc25-c8641bbe78ad',
                    'be8e075d-bd24-4638-b9d7-786268b8af52' => 'be8e075d-bd24-4638-b9d7-786268b8af52',
                ],
                Document::class    => [
                    // empty
                ],
                Distributor::class => [
                    // empty
                ],
                Location::class    => [
                    // empty
                ],
            ],
            $collected->getData()?->getData(),
        );
    }
}
