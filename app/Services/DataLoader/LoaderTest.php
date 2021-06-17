<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Models\Model;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\TypeWithId;
use Exception;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Loader
 */
class LoaderTest extends TestCase {
    /**
     * @covers ::update
     */
    public function testUpdateById(): void {
        // Prepare
        $id     = $this->faker->uuid;
        $type   = Mockery::mock(Type::class, TypeWithId::class);
        $model  = Mockery::mock(Model::class);
        $loader = Mockery::mock(Loader::class);
        $loader->shouldAllowMockingProtectedMethods();
        $loader->makePartial();
        $loader
            ->shouldReceive('process')
            ->with($type)
            ->once()
            ->andReturn($model);
        $loader
            ->shouldReceive('getObject')
            ->with([])
            ->once()
            ->andReturn($type);
        $loader
            ->shouldReceive('isModelExists')
            ->with($id)
            ->once()
            ->andReturn(true);
        $loader
            ->shouldReceive('getObjectById')
            ->with($id)
            ->andReturn($type);

        // Test
        $this->assertEquals($model, $loader->update($id));
    }

    /**
     * @covers ::update
     */
    public function testUpdateByType(): void {
        // Prepare
        $value  = ['id' => $this->faker->uuid];
        $type   = new class($value) extends Type implements TypeWithId {
            public string $id;
        };
        $model  = Mockery::mock(Model::class);
        $loader = Mockery::mock(Loader::class);
        $loader->shouldAllowMockingProtectedMethods();
        $loader->makePartial();
        $loader
            ->shouldReceive('process')
            ->with($type)
            ->once()
            ->andReturn($model);
        $loader
            ->shouldReceive('getObject')
            ->never();
        $loader
            ->shouldReceive('isModelExists')
            ->with($type->id)
            ->once()
            ->andReturn(true);

        // Test
        $this->assertEquals($model, $loader->update($type));
    }

    /**
     * @covers ::update
     */
    public function testUpdateByIdModelNotExists(): void {
        // Prepare
        $e      = new Exception(__METHOD__);
        $id     = $this->faker->uuid;
        $type   = Mockery::mock(Type::class, TypeWithId::class);
        $model  = Mockery::mock(Model::class);
        $loader = Mockery::mock(Loader::class);
        $loader->shouldAllowMockingProtectedMethods();
        $loader->makePartial();
        $loader
            ->shouldReceive('process')
            ->never();
        $loader
            ->shouldReceive('getObject')
            ->with([])
            ->once()
            ->andReturn($type);
        $loader
            ->shouldReceive('isModelExists')
            ->with($id)
            ->once()
            ->andReturn(false);
        $loader
            ->shouldReceive('getObjectById')
            ->never();
        $loader
            ->shouldReceive('getModelNotFoundException')
            ->with($id)
            ->once()
            ->andThrow($e);

        // Test
        $this->expectExceptionObject($e);
        $this->assertEquals($model, $loader->update($id));
    }

    /**
     * @covers ::update
     */
    public function testUpdateByTypeModelNotExists(): void {
        // Prepare
        $e      = new Exception(__METHOD__);
        $value  = ['id' => $this->faker->uuid];
        $type   = new class($value) extends Type implements TypeWithId {
            public string $id;
        };
        $model  = Mockery::mock(Model::class);
        $loader = Mockery::mock(Loader::class);
        $loader->shouldAllowMockingProtectedMethods();
        $loader->makePartial();
        $loader
            ->shouldReceive('process')
            ->never();
        $loader
            ->shouldReceive('getObject')
            ->never();
        $loader
            ->shouldReceive('isModelExists')
            ->with($type->id)
            ->once()
            ->andReturn(false);
        $loader
            ->shouldReceive('getModelNotFoundException')
            ->with($type->id)
            ->once()
            ->andThrow($e);

        // Test
        $this->expectExceptionObject($e);
        $this->assertEquals($model, $loader->update($type));
    }

    /**
     * @covers ::update
     */
    public function testUpdateByIdTypeWithoutId(): void {
        // Prepare
        $id     = $this->faker->uuid;
        $type   = Mockery::mock(Type::class);
        $model  = Mockery::mock(Model::class);
        $loader = Mockery::mock(Loader::class);
        $loader->shouldAllowMockingProtectedMethods();
        $loader->makePartial();
        $loader
            ->shouldReceive('process')
            ->with($type)
            ->once()
            ->andReturn($model);
        $loader
            ->shouldReceive('getObject')
            ->with([])
            ->once()
            ->andReturn($type);
        $loader
            ->shouldReceive('isModelExists')
            ->never();
        $loader
            ->shouldReceive('getObjectById')
            ->with($id)
            ->andReturn($type);

        // Test
        $this->assertEquals($model, $loader->update($id));
    }

    /**
     * @covers ::update
     */
    public function testUpdateByTypeTypeWithoutId(): void {
        // Prepare
        $value  = ['id' => $this->faker->uuid];
        $type   = new class($value) extends Type {
            public string $id;
        };
        $model  = Mockery::mock(Model::class);
        $loader = Mockery::mock(Loader::class);
        $loader->shouldAllowMockingProtectedMethods();
        $loader->makePartial();
        $loader
            ->shouldReceive('process')
            ->with($type)
            ->once()
            ->andReturn($model);
        $loader
            ->shouldReceive('getObject')
            ->never();
        $loader
            ->shouldReceive('isModelExists')
            ->never();

        // Test
        $this->assertEquals($model, $loader->update($type));
    }

    /**
     * @covers ::create
     */
    public function testCreateById(): void {
        // Prepare
        $id     = $this->faker->uuid;
        $type   = Mockery::mock(Type::class);
        $model  = Mockery::mock(Model::class);
        $loader = Mockery::mock(Loader::class);
        $loader->shouldAllowMockingProtectedMethods();
        $loader->makePartial();
        $loader
            ->shouldReceive('process')
            ->with($type)
            ->once()
            ->andReturn($model);
        $loader
            ->shouldReceive('getObject')
            ->never();
        $loader
            ->shouldReceive('isModelExists')
            ->never();
        $loader
            ->shouldReceive('getObjectById')
            ->with($id)
            ->andReturn($type);

        // Test
        $this->assertEquals($model, $loader->create($id));
    }

    /**
     * @covers ::create
     */
    public function testCreateByType(): void {
        // Prepare
        $value  = ['id' => $this->faker->uuid];
        $type   = new class($value) extends Type {
            public string $id;
        };
        $model  = Mockery::mock(Model::class);
        $loader = Mockery::mock(Loader::class);
        $loader->shouldAllowMockingProtectedMethods();
        $loader->makePartial();
        $loader
            ->shouldReceive('process')
            ->with($type)
            ->once()
            ->andReturn($model);
        $loader
            ->shouldReceive('getObject')
            ->never();
        $loader
            ->shouldReceive('getObjectById')
            ->never();
        $loader
            ->shouldReceive('isModelExists')
            ->never();

        // Test
        $this->assertEquals($model, $loader->update($type));
    }
}
