<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Collector;

use App\Utils\Eloquent\Model;
use Mockery;
use stdClass;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\DataLoader\Collector\Collector
 */
class CollectorTest extends TestCase {
    public function testCollect(): void {
        // Mock
        $collector = $this->app->make(Collector::class);
        $object    = new stdClass();

        $a = Mockery::mock(Data::class);
        $a
            ->shouldReceive('collect')
            ->with($object)
            ->once()
            ->andReturns();

        $b = Mockery::mock(Data::class);
        $b
            ->shouldReceive('collect')
            ->with($object)
            ->once()
            ->andReturns();

        // Prepare
        $collector->subscribe($a);
        $collector->subscribe($b);

        // Test
        $collector->collect($object);
    }

    public function testModelEvents(): void {
        // Mock
        $collector = $this->app->make(Collector::class);
        $model     = new class() extends Model {
            // empty
        };

        $a = Mockery::mock(Data::class);
        $a
            ->shouldReceive('collectObjectChange')
            ->with($model)
            ->once()
            ->andReturns();
        $a
            ->shouldReceive('collectObjectDeletion')
            ->with($model)
            ->once()
            ->andReturns();

        $b = Mockery::mock(Data::class);
        $b
            ->shouldReceive('collectObjectChange')
            ->with($model)
            ->once()
            ->andReturns();
        $b
            ->shouldReceive('collectObjectDeletion')
            ->with($model)
            ->once()
            ->andReturns();

        // Prepare
        $collector->subscribe($a);
        $collector->subscribe($b);

        // Test
        $collector->modelSaved($model);
        $collector->modelDeleted($model);
    }
}
