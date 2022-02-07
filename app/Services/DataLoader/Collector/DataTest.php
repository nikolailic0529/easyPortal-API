<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Collector;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\Location;
use App\Models\Reseller;
use stdClass;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Collector\Data
 */
class DataTest extends TestCase {
    /**
     * @covers ::collect
     * @covers ::getData
     * @covers ::get
     */
    public function testCollect(): void {
        $data   = new Data();
        $uuidA  = $this->faker->uuid;
        $uuidB  = $this->faker->uuid;
        $assetA = Asset::factory()->make(['id' => null]);
        $assetB = Asset::factory()->make(['id' => $uuidA]);

        $data->collect($assetA);
        $data->collect($assetB);
        $data->collect(Customer::factory()->make(['id' => $uuidB]));

        $this->assertEquals([], $data->get(stdClass::class));
        $this->assertEquals([$uuidA => $uuidA], $data->get(Asset::class));
        $this->assertEquals(
            [
                $uuidB               => $uuidB,
                $assetA->customer_id => $assetA->customer_id,
                $assetB->customer_id => $assetB->customer_id,
            ],
            $data->get(Customer::class),
        );
        $this->assertEquals(
            [
                Distributor::class => [],
                Reseller::class    => [
                    $assetA->reseller_id => $assetA->reseller_id,
                    $assetB->reseller_id => $assetB->reseller_id,
                ],
                Customer::class    => [
                    $uuidB               => $uuidB,
                    $assetA->customer_id => $assetA->customer_id,
                    $assetB->customer_id => $assetB->customer_id,
                ],
                Document::class    => [],
                Location::class    => [
                    $assetA->location_id => $assetA->location_id,
                    $assetB->location_id => $assetB->location_id,
                ],
                Asset::class       => [
                    $uuidA => $uuidA,
                ],
            ],
            $data->getData(),
        );
    }
}
