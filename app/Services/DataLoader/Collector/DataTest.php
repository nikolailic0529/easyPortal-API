<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Collector;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Data\Location;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\Reseller;
use App\Utils\Eloquent\Model;
use Mockery;
use stdClass;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\DataLoader\Collector\Data
 */
class DataTest extends TestCase {
    public function testCollect(): void {
        $data   = new Data();
        $uuidA  = $this->faker->uuid();
        $uuidB  = $this->faker->uuid();
        $assetA = Asset::factory()->make([
            'id'          => null,
            'reseller_id' => $this->faker->uuid(),
            'customer_id' => $this->faker->uuid(),
            'location_id' => $this->faker->uuid(),
        ]);
        $assetB = Asset::factory()->make([
            'id'          => $uuidA,
            'reseller_id' => $this->faker->uuid(),
            'customer_id' => $this->faker->uuid(),
            'location_id' => $this->faker->uuid(),
        ]);

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

    public function testIsEmpty(): void {
        self::assertTrue((new Data())->isEmpty());
        self::assertFalse((new Data())->collect(Asset::factory()->make())->isEmpty());
    }

    public function testIsDirty(): void {
        self::assertFalse((new Data())->isDirty());
        self::assertTrue((new Data())->collectObjectChange(Asset::factory()->make())->isDirty());
    }

    public function testCollectObjectChangeModel(): void {
        $model = Mockery::mock(Model::class);
        $data  = Mockery::mock(Data::class);
        $data->shouldAllowMockingProtectedMethods();
        $data->makePartial();
        $data
            ->shouldReceive('collect')
            ->with($model)
            ->twice();

        self::assertFalse($data->isDirty());

        $data->collectObjectChange($model);
        $data->collectObjectChange($model);

        self::assertTrue($data->isDirty());
    }

    public function testCollectObjectChangeObject(): void {
        $object = new stdClass();
        $data   = Mockery::mock(Data::class);
        $data->shouldAllowMockingProtectedMethods();
        $data->makePartial();

        self::assertFalse($data->isDirty());

        $data->collectObjectChange($object);
        $data->collectObjectChange($object);

        self::assertFalse($data->isDirty());
    }

    public function testCollectObjectDeletionModel(): void {
        $model = Mockery::mock(Model::class);
        $data  = Mockery::mock(Data::class);
        $data->shouldAllowMockingProtectedMethods();
        $data->makePartial();
        $data
            ->shouldReceive('collect')
            ->with($model)
            ->twice();

        self::assertFalse($data->isDirty());

        $data->collectObjectDeletion($model);
        $data->collectObjectDeletion($model);

        self::assertTrue($data->isDirty());
    }

    public function testCollectObjectDeletionObject(): void {
        $object = new stdClass();
        $data   = Mockery::mock(Data::class);
        $data->shouldAllowMockingProtectedMethods();
        $data->makePartial();

        self::assertFalse($data->isDirty());

        $data->collectObjectDeletion($object);
        $data->collectObjectDeletion($object);

        self::assertFalse($data->isDirty());
    }

    public function testAddAll(): void {
        $data = new Data();

        $data
            ->addAll(Asset::class, [
                '8524c29d-579b-42ec-943f-b23d940d1e96',
                'a2ab8ce9-38e2-4814-880a-082795442efe',
            ])
            ->addAll(Customer::class, [
                '603f2af7-e7a1-4ec6-8347-d88756c4f023',
            ]);

        self::assertEquals(
            [
                Reseller::class    => [
                    // empty
                ],
                Customer::class    => [
                    '603f2af7-e7a1-4ec6-8347-d88756c4f023' => '603f2af7-e7a1-4ec6-8347-d88756c4f023',
                ],
                Asset::class       => [
                    '8524c29d-579b-42ec-943f-b23d940d1e96' => '8524c29d-579b-42ec-943f-b23d940d1e96',
                    'a2ab8ce9-38e2-4814-880a-082795442efe' => 'a2ab8ce9-38e2-4814-880a-082795442efe',
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
            $data->getData(),
        );
    }

    public function testAddData(): void {
        $a = new Data();
        $b = new Data();
        $c = new class() extends Data {
            /**
             * @inheritDoc
             */
            public function getData(): array {
                return [
                    Reseller::class => [
                        'd467b4d2-db00-433a-98d4-219a33c1c683' => 'd467b4d2-db00-433a-98d4-219a33c1c683',
                    ],
                    Model::class    => [
                        'c8989f40-010a-4da3-be8a-1eda0340d543' => 'c8989f40-010a-4da3-be8a-1eda0340d543',
                    ],
                ];
            }
        };

        $a->add(Asset::class, 'b20a9fdc-8d32-43e8-8e27-7163f8fccb7b');
        $a->add(Asset::class, '3dfc5739-aa7e-42dc-9e3b-7eafe4ea2721');
        $a->add(Customer::class, 'f3840603-d07e-46ce-909c-37af899b4d1a');

        $b->add(Customer::class, '093c3e47-2234-4598-9d29-78aaa1430cb2');
        $b->add(Customer::class, 'f3840603-d07e-46ce-909c-37af899b4d1a');
        $b->add(Document::class, '60b18ef0-0ab9-4990-8e0b-795afecffba2');

        $a->addData($b)->addData($c);

        self::assertEquals(
            [
                Reseller::class    => [
                    'd467b4d2-db00-433a-98d4-219a33c1c683' => 'd467b4d2-db00-433a-98d4-219a33c1c683',
                ],
                Customer::class    => [
                    'f3840603-d07e-46ce-909c-37af899b4d1a' => 'f3840603-d07e-46ce-909c-37af899b4d1a',
                    '093c3e47-2234-4598-9d29-78aaa1430cb2' => '093c3e47-2234-4598-9d29-78aaa1430cb2',
                ],
                Asset::class       => [
                    'b20a9fdc-8d32-43e8-8e27-7163f8fccb7b' => 'b20a9fdc-8d32-43e8-8e27-7163f8fccb7b',
                    '3dfc5739-aa7e-42dc-9e3b-7eafe4ea2721' => '3dfc5739-aa7e-42dc-9e3b-7eafe4ea2721',
                ],
                Document::class    => [
                    '60b18ef0-0ab9-4990-8e0b-795afecffba2' => '60b18ef0-0ab9-4990-8e0b-795afecffba2',
                ],
                Distributor::class => [
                    // empty
                ],
                Location::class    => [
                    // empty
                ],
            ],
            $a->getData(),
        );
    }

    public function testDeleteAll(): void {
        $a = new Data();

        $a->add(Asset::class, 'b20a9fdc-8d32-43e8-8e27-7163f8fccb7b');
        $a->add(Asset::class, '3dfc5739-aa7e-42dc-9e3b-7eafe4ea2721');
        $a->add(Customer::class, 'f3840603-d07e-46ce-909c-37af899b4d1a');

        self::assertEquals(
            [
                Reseller::class    => [
                    // empty
                ],
                Customer::class    => [
                    'f3840603-d07e-46ce-909c-37af899b4d1a' => 'f3840603-d07e-46ce-909c-37af899b4d1a',
                ],
                Asset::class       => [
                    '3dfc5739-aa7e-42dc-9e3b-7eafe4ea2721' => '3dfc5739-aa7e-42dc-9e3b-7eafe4ea2721',
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
            $a
                ->deleteAll(Asset::class, [
                    'b20a9fdc-8d32-43e8-8e27-7163f8fccb7b',
                ])
                ->getData(),
        );

        self::assertEquals(
            [
                Reseller::class    => [
                    // empty
                ],
                Customer::class    => [
                    // empty
                ],
                Asset::class       => [
                    '3dfc5739-aa7e-42dc-9e3b-7eafe4ea2721' => '3dfc5739-aa7e-42dc-9e3b-7eafe4ea2721',
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
            $a
                ->deleteAll(Customer::class)
                ->getData(),
        );
    }
}
