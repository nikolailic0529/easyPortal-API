<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Collector;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\Location;
use App\Models\Reseller;
use App\Utils\Eloquent\Model;
use Mockery;
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
        $uuidA  = $this->faker->uuid();
        $uuidB  = $this->faker->uuid();
        $assetA = Asset::factory()->make(['id' => null]);
        $assetB = Asset::factory()->make(['id' => $uuidA]);

        $data->collect($assetA);
        $data->collect($assetB);
        $data->collect(Customer::factory()->make(['id' => $uuidB]));

        self::assertEquals([], $data->get(stdClass::class));
        self::assertEquals(
            [
                $uuidA            => $uuidA,
                $assetA->getKey() => $assetA->getKey(),
            ],
            $data->get(Asset::class),
        );
        self::assertEquals(
            [
                $uuidB               => $uuidB,
                $assetA->customer_id => $assetA->customer_id,
                $assetB->customer_id => $assetB->customer_id,
            ],
            $data->get(Customer::class),
        );
        self::assertEquals(
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
                    $uuidA            => $uuidA,
                    $assetA->getKey() => $assetA->getKey(),
                ],
            ],
            $data->getData(),
        );
    }

    /**
     * @covers ::isEmpty
     */
    public function testIsEmpty(): void {
        self::assertTrue((new Data())->isEmpty());
        self::assertFalse((new Data())->collect(Asset::factory()->make())->isEmpty());
    }

    /**
     * @covers ::isDirty
     */
    public function testIsDirty(): void {
        self::assertFalse((new Data())->isDirty());
        self::assertTrue((new Data())->collectObjectChange(Asset::factory()->make())->isDirty());
    }

    /**
     * @covers ::collectObjectChange
     */
    public function testCollectObjectChangeModel(): void {
        $model = Mockery::mock(Model::class);
        $data  = Mockery::mock(Data::class);
        $data->shouldAllowMockingProtectedMethods();
        $data->makePartial();
        $data
            ->shouldReceive('isModelChanged')
            ->once()
            ->andReturn(true);

        self::assertFalse($data->isDirty());

        $data->collectObjectChange($model);
        $data->collectObjectChange($model);

        self::assertTrue($data->isDirty());
    }

    /**
     * @covers ::collectObjectChange
     */
    public function testCollectObjectChangeObject(): void {
        $object = new stdClass();
        $data   = Mockery::mock(Data::class);
        $data->shouldAllowMockingProtectedMethods();
        $data->makePartial();
        $data
            ->shouldReceive('isModelChanged')
            ->never();

        self::assertFalse($data->isDirty());

        $data->collectObjectChange($object);
        $data->collectObjectChange($object);

        self::assertFalse($data->isDirty());
    }
}
