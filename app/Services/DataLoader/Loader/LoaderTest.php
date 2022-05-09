<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader;

use App\Utils\Eloquent\Model;
use Exception;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Loader\Loader
 */
class LoaderTest extends TestCase {
    /**
     * @covers ::process
     */
    public function testProcessModelNotExists(): void {
        $this->markTestIncomplete('Not implemented!');

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
}
