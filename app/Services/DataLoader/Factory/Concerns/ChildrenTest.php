<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Services\DataLoader\Schema\Type;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

use function tap;

/**
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\Children
 * @internal
 */
class ChildrenTest extends TestCase {
    /**
     * @covers ::children
     */
    public function testChildren(): void {
        $type                 = (new class() extends Type {
            public string $id;
            public string $key;
            public string $property;
        })::class;
        $model                = new class() extends Model {
            // empty
        };
        $modelShouldBeUpdated = (clone $model)->forceFill([
            'id'       => 'acddf0a2-2d07-4113-ac8c-a2176ddf096c',
            'key'      => $this->faker->uuid,
            'property' => $this->faker->uuid,
        ]);
        $modelShouldBeReused  = (clone $model)->forceFill([
            'id'       => 'edb4853a-796c-48af-a127-ac43492cc6b7',
            'property' => $this->faker->uuid,
        ]);
        $modelShouldBeIgnored = (clone $model)->forceFill([
            'id'       => '81ab5bcb-abb9-4b2b-ad6c-d92b4fd7b5e8',
            'property' => $modelShouldBeReused->property,
        ]);
        $modelShouldBeDeleted = (clone $model)->forceFill([
            'id'       => 'efa31a49-2bd0-4e22-a659-d63a9827d770',
            'property' => $this->faker->uuid,
        ]);
        $typeShouldBeCreated  = new $type([
            'id'       => '561ecb81-db60-4fc5-b406-ab2f0d317bfa',
            'key'      => $this->faker->uuid,
            'property' => $this->faker->uuid,
        ]);
        $typeShouldBeUpdated  = new $type([
            'key'      => $modelShouldBeUpdated->key,
            'property' => $this->faker->uuid,
        ]);
        $factory              = function (Type $type) use ($model): Model {
            return (clone $model)->forceFill([
                'id'       => $type->id ?? $this->faker->uuid,
                'key'      => $type->key ?? null,
                'property' => $type->property ?? null,
            ]);
        };
        $compare              = static function (Model $a, Model $b): int {
            return $a->key <=> $b->key;
        };
        $isReusable           = static function (Model $model) use ($modelShouldBeIgnored): bool {
            return $model->getKey() !== $modelShouldBeIgnored->getKey();
        };
        $children             = new class() {
            use Children {
                children as public;
            }
        };

        $existing = new Collection([$modelShouldBeUpdated, $modelShouldBeDeleted, $modelShouldBeReused]);
        $entries  = [$typeShouldBeCreated, $typeShouldBeUpdated];
        $actual   = $children->children($existing, $entries, $factory, $compare, $isReusable);
        $expected = new Collection([
            tap(clone $model, static function (Model $model) use ($modelShouldBeReused, $typeShouldBeCreated): void {
                $model->id       = $modelShouldBeReused->getKey();
                $model->key      = $typeShouldBeCreated->key;
                $model->property = $typeShouldBeCreated->property;
                $model->exists   = true;
            }),
            (clone $model)->forceFill([
                'id'       => $modelShouldBeUpdated->getKey(),
                'key'      => $modelShouldBeUpdated->key,
                'property' => $typeShouldBeUpdated->property,
            ]),
        ]);

        $this->assertInstanceOf($existing::class, $actual);
        $this->assertEquals($expected, $actual);
    }
}
