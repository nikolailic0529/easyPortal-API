<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Models\Data\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Date;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Logger\Listeners\EloquentObject
 */
class EloquentObjectTest extends TestCase {
    public function testGetChanges(): void {
        $model  = Status::factory()->create();
        $object = new EloquentObject($model);

        self::assertEquals([], $object->getChanges());

        $previousKey       = $model->key;
        $previousCreatedAt = $model->created_at;
        $model->key        = $this->faker->uuid();
        $model->created_at = Date::now()->addDay();
        $actual            = [];

        $model->saved(static function () use ($object, &$actual): void {
            $actual = $object->getChanges();
        });
        $model->save();

        self::assertEquals([
            'key'        => [
                'value'    => $model->key,
                'previous' => $previousKey,
            ],
            'created_at' => [
                'value'    => $model->created_at->format($model->getDateFormat()),
                'previous' => $previousCreatedAt->format($model->getDateFormat()),
            ],
        ], $actual);
    }

    public function testGetProperties(): void {
        $model  = Status::factory()->make();
        $object = new EloquentObject($model);

        self::assertEquals([
            'id'          => [
                'value'    => $model->getKey(),
                'previous' => null,
            ],
            'key'         => [
                'value'    => $model->key,
                'previous' => null,
            ],
            'name'        => [
                'value'    => $model->name,
                'previous' => null,
            ],
            'created_at'  => [
                'value'    => $model->created_at->format($model->getDateFormat()),
                'previous' => null,
            ],
            'updated_at'  => [
                'value'    => $model->updated_at->format($model->getDateFormat()),
                'previous' => null,
            ],
            'deleted_at'  => [
                'value'    => $model->deleted_at?->format($model->getDateFormat()),
                'previous' => null,
            ],
            'object_type' => [
                'value'    => $model->object_type,
                'previous' => null,
            ],
        ], $object->getProperties());
    }

    public function testIsSoftDeletable(): void {
        self::assertTrue((new EloquentObject(Mockery::mock(Model::class, SoftDeletes::class)))->isSoftDeletable());
        self::assertFalse((new EloquentObject(Mockery::mock(Model::class)))->isSoftDeletable());
    }
}
