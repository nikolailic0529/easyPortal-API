<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Collector;

use Mockery;
use stdClass;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Collector\Collector
 */
class CollectorTest extends TestCase {
    /**
     * @covers ::collect
     * @covers ::subscribe
     */
    public function testCollect(): void {
        // Mock
        $collector = new Collector();
        $object    = new stdClass();

        $a = Mockery::mock(Data::class);
        $a
            ->shouldReceive('add')
            ->with($object)
            ->once()
            ->andReturns();

        $b = Mockery::mock(Data::class);
        $b
            ->shouldReceive('add')
            ->with($object)
            ->once()
            ->andReturns();

        // Prepare
        $collector->subscribe($a);
        $collector->subscribe($b);

        // Test
        $collector->collect($object);
    }
}
