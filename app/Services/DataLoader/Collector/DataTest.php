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
        $data  = new Data();
        $uuidA = $this->faker->uuid;
        $uuidB = $this->faker->uuid;

        $data->collect(Asset::factory()->make(['id' => null]));
        $data->collect(Asset::factory()->make(['id' => $uuidA]));
        $data->collect(Customer::factory()->make(['id' => $uuidB]));

        $this->assertEquals([], $data->get(stdClass::class));
        $this->assertEquals([$uuidA => $uuidA], $data->get(Asset::class));
        $this->assertEquals([$uuidB => $uuidB], $data->get(Customer::class));
        $this->assertEquals(
            [
                Distributor::class => [],
                Reseller::class    => [],
                Customer::class    => [$uuidB => $uuidB],
                Document::class    => [],
                Location::class    => [],
                Asset::class       => [$uuidA => $uuidA],
            ],
            $data->getData(),
        );
    }
}
