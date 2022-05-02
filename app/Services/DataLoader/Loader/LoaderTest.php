<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader;

use App\Services\DataLoader\Collector\Collector;
use App\Services\DataLoader\Events\DataImported;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Loader\Loader
 */
class LoaderTest extends TestCase {
    /**
     * @covers ::create
     */
    public function testCreateModelNotExists(): void {
        // Prepare
        $e      = new Exception(__METHOD__);
        $id     = $this->faker->uuid();
        $model  = Mockery::mock(Model::class);
        $loader = Mockery::mock(Loader::class);
        $loader->shouldAllowMockingProtectedMethods();
        $loader->makePartial();
        $loader
            ->shouldReceive('run')
            ->never();
        $loader
            ->shouldReceive('getObjectById')
            ->with($id)
            ->once()
            ->andReturn(null);
        $loader
            ->shouldReceive('getModelNotFoundException')
            ->with($id)
            ->once()
            ->andThrow($e);

        // Test
        self::expectExceptionObject($e);
        self::assertEquals($model, $loader->create($id));
    }

    /**
     * @covers ::create
     */
    public function testCreate(): void {
        // Fake
        Event::fake(DataImported::class);

        // Prepare
        $id        = $this->faker->uuid();
        $type      = Mockery::mock(Type::class);
        $model     = Mockery::mock(Model::class);
        $collector = Mockery::mock(Collector::class);
        $collector
            ->shouldReceive('subscribe')
            ->once()
            ->andReturns();
        $dispatcher = $this->app->make(Dispatcher::class);

        $loader = Mockery::mock(Loader::class);
        $loader->shouldAllowMockingProtectedMethods();
        $loader->makePartial();
        $loader
            ->shouldReceive('process')
            ->with($type)
            ->once()
            ->andReturn($model);
        $loader
            ->shouldReceive('getObjectById')
            ->with($id)
            ->andReturn($type);
        $loader
            ->shouldReceive('getCollector')
            ->once()
            ->andReturn($collector);
        $loader
            ->shouldReceive('getDispatcher')
            ->once()
            ->andReturn($dispatcher);

        // Test
        self::assertEquals($model, $loader->create($id));

        Event::assertDispatched(DataImported::class);
    }
}
