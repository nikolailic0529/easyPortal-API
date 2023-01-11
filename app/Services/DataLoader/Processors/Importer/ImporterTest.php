<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer;

use App\Services\DataLoader\Collector\Data;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Resolver\Resolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\TypeWithKey;
use App\Utils\Eloquent\Model;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\DataLoader\Processors\Importer\Importer
 */
class ImporterTest extends TestCase {
    public function testProcessModelObject(): void {
        $data  = new Data();
        $state = new ImporterState();
        $model = Mockery::mock(Model::class);
        $model
            ->shouldReceive('delete')
            ->once()
            ->andReturn(true);

        $importer = Mockery::mock(Importer::class);
        $importer->shouldAllowMockingProtectedMethods();
        $importer->makePartial();

        $importer->process($state, $data, new ModelObject([
            'model' => $model,
        ]));

        self::assertEquals(
            [
                'from'      => null,
                'force'     => false,
                'updated'   => 0,
                'created'   => 0,
                'deleted'   => 1,
                'offset'    => null,
                'index'     => 0,
                'limit'     => null,
                'total'     => null,
                'processed' => 0,
                'success'   => 0,
                'failed'    => 0,
            ],
            $state->toArray(),
        );
    }

    public function testProcessType(): void {
        // Prepare
        $id       = $this->faker->uuid();
        $data     = new Data();
        $type     = new class(['id' => $id]) extends Type implements TypeWithKey {
            public string $id;

            public function getKey(): string {
                return $this->id;
            }
        };
        $force    = $this->faker->boolean();
        $factory  = Mockery::mock(Factory::class);
        $resolver = Mockery::mock(Resolver::class);
        $importer = Mockery::mock(Importer::class);
        $importer->shouldAllowMockingProtectedMethods();
        $importer->makePartial();
        $importer
            ->shouldReceive('getResolver')
            ->twice()
            ->andReturn($resolver);
        $importer
            ->shouldReceive('getFactory')
            ->twice()
            ->andReturn($factory);
        $factory
            ->shouldReceive('create')
            ->with($type, $force)
            ->twice()
            ->andReturn(null);

        // Model not exists
        $resolver
            ->shouldReceive('get')
            ->with($id)
            ->once()
            ->andReturn(false);

        $state = new ImporterState([
            'force' => $force,
        ]);

        $importer->process($state, $data, $type);

        self::assertEquals(
            [
                'from'      => null,
                'force'     => $force,
                'updated'   => 0,
                'created'   => 1,
                'deleted'   => 0,
                'offset'    => null,
                'index'     => 0,
                'limit'     => null,
                'total'     => null,
                'processed' => 0,
                'success'   => 0,
                'failed'    => 0,
            ],
            $state->toArray(),
        );

        // Model exists
        $resolver
            ->shouldReceive('get')
            ->with($id)
            ->once()
            ->andReturn(true);

        $state = new ImporterState([
            'force' => $force,
        ]);

        $importer->process($state, $data, $type);

        self::assertEquals(
            [
                'from'      => null,
                'force'     => $force,
                'updated'   => 1,
                'created'   => 0,
                'deleted'   => 0,
                'offset'    => null,
                'index'     => 0,
                'limit'     => null,
                'total'     => null,
                'processed' => 0,
                'success'   => 0,
                'failed'    => 0,
            ],
            $state->toArray(),
        );
    }
}
