<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\SmartSave;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Utils\Eloquent\SmartSave\BatchInsert
 */
class BatchInsertTest extends TestCase {
    public function testSave(): void {
        $inserts = [['a' => 'a']];
        $model   = Mockery::mock(Model::class);
        $insert  = Mockery::mock(BatchInsertTest_BatchInsert::class, [$model, $inserts]);
        $insert->shouldAllowMockingProtectedMethods();
        $insert->makePartial();
        $insert
            ->shouldReceive('insert')
            ->once()
            ->andReturns();
        $insert
            ->shouldReceive('upsert')
            ->never();

        $insert->save();

        self::assertNull($insert->getModel());
        self::assertEmpty($insert->getInserts());
    }

    public function testSaveEmptyNoModel(): void {
        $inserts = [['a' => 'a']];
        $model   = null;
        $insert  = Mockery::mock(BatchInsertTest_BatchInsert::class, [$model, $inserts]);
        $insert->shouldAllowMockingProtectedMethods();
        $insert->makePartial();
        $insert
            ->shouldReceive('insert')
            ->never();
        $insert
            ->shouldReceive('upsert')
            ->never();

        $insert->save();

        self::assertNull($insert->getModel());
        self::assertEmpty($insert->getInserts());
    }

    public function testSaveEmptyNoInserts(): void {
        $inserts = [];
        $model   = Mockery::mock(Model::class, Upsertable::class);
        $insert  = Mockery::mock(BatchInsertTest_BatchInsert::class, [$model, $inserts]);
        $insert->shouldAllowMockingProtectedMethods();
        $insert->makePartial();
        $insert
            ->shouldReceive('insert')
            ->never();
        $insert
            ->shouldReceive('upsert')
            ->never();

        $insert->save();

        self::assertNull($insert->getModel());
        self::assertEmpty($insert->getInserts());
    }

    public function testSaveUpsertable(): void {
        $inserts = [['a' => 'a']];
        $model   = Mockery::mock(Model::class, Upsertable::class);
        $insert  = Mockery::mock(BatchInsertTest_BatchInsert::class, [$model, $inserts]);
        $insert->shouldAllowMockingProtectedMethods();
        $insert->makePartial();
        $insert
            ->shouldReceive('insert')
            ->never();
        $insert
            ->shouldReceive('upsert')
            ->once()
            ->andReturns();

        $insert->save();

        self::assertNull($insert->getModel());
        self::assertEmpty($insert->getInserts());
    }

    public function testInsertSingle(): void {
        $row     = ['a' => 'a'];
        $inserts = [$row];
        $query   = Mockery::mock(Builder::class);
        $query
            ->shouldReceive('insert')
            ->with($row)
            ->once()
            ->andReturn(0);

        $insert = Mockery::mock(BatchInsertTest_BatchInsert::class, [null, $inserts]);
        $insert->shouldAllowMockingProtectedMethods();
        $insert->makePartial();
        $insert
            ->shouldReceive('query')
            ->once()
            ->andReturn($query);

        $insert->insert();
    }

    public function testInsertMultiple(): void {
        $row     = ['a' => 'a'];
        $inserts = [$row, $row];
        $query   = Mockery::mock(Builder::class);
        $query
            ->shouldReceive('insert')
            ->with($inserts)
            ->once()
            ->andReturn(0);

        $insert = Mockery::mock(BatchInsertTest_BatchInsert::class, [null, $inserts]);
        $insert->shouldAllowMockingProtectedMethods();
        $insert->makePartial();
        $insert
            ->shouldReceive('query')
            ->once()
            ->andReturn($query);

        $insert->insert();
    }

    public function testUpsert(): void {
        $inserts = [
            [
                'id'    => $this->faker->uuid(),
                'a'     => 'a',
                'b'     => 'b',
                'c'     => 'c',
                'count' => 123,
            ],
        ];
        $unique  = ['a', 'b', 'c'];
        $model   = Mockery::mock(Model::class, Upsertable::class);
        $model
            ->shouldReceive('getKeyName')
            ->once()
            ->andReturn('id');
        $model
            ->shouldReceive('getCreatedAtColumn')
            ->once()
            ->andReturn('created_at');
        $model
            ->shouldReceive('getUniqueKey')
            ->once()
            ->andReturn($unique);

        $query = Mockery::mock(Builder::class);
        $query
            ->shouldReceive('upsert')
            ->with($inserts, $unique, ['count'])
            ->once()
            ->andReturn(0);

        $insert = Mockery::mock(BatchInsertTest_BatchInsert::class, [$model, $inserts]);
        $insert->shouldAllowMockingProtectedMethods();
        $insert->makePartial();
        $insert
            ->shouldReceive('query')
            ->once()
            ->andReturn($query);
        $insert
            ->shouldReceive('insert')
            ->never();

        $insert->upsert();
        $insert->reset();
    }

    public function testUpsertNonUpsertable(): void {
        $insert = Mockery::mock(BatchInsert::class, [Mockery::mock(Model::class)]);
        $insert->shouldAllowMockingProtectedMethods();
        $insert->makePartial();
        $insert
            ->shouldReceive('query')
            ->never();
        $insert
            ->shouldReceive('insert')
            ->once()
            ->andReturns();

        $insert->upsert();
    }
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class BatchInsertTest_BatchInsert extends BatchInsert {
    /**
     * @param array<array<string,mixed>> $inserts
     */
    public function __construct(?Model $model, array $inserts = []) {
        parent::__construct();

        $this->model   = $model;
        $this->inserts = $inserts;
    }

    public function getModel(): ?Model {
        return $this->model;
    }

    /**
     * @return array<array<string,mixed>>
     */
    public function getInserts(): array {
        return $this->inserts;
    }
}
