<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Services\DataLoader\Cache\Key;
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
        $id                   = 1;
        $model                = new class() extends Model {
            // empty
        };
        $modelShouldBeUpdated = (clone $model)->forceFill([
            'id'       => $id++,
            'key'      => $this->faker->uuid(),
            'property' => $this->faker->uuid(),
        ]);
        $modelShouldBeIgnored = (clone $model)->forceFill([
            'id'       => $id++,
            'property' => $this->faker->uuid(),
        ]);
        $modelShouldBeReused  = (clone $model)->forceFill([
            'id'       => $id++,
            'property' => $this->faker->uuid(),
        ]);
        $modelShouldBeDeleted = (clone $model)->forceFill([
            'id'       => $id++,
            'property' => $this->faker->uuid(),
        ]);
        $typeShouldBeCreated  = new ChildrenTest_Type([
            'id'       => '561ecb81-db60-4fc5-b406-ab2f0d317bfa',
            'key'      => $this->faker->uuid(),
            'property' => $this->faker->uuid(),
        ]);
        $typeShouldBeUpdated  = new ChildrenTest_Type([
            'key'      => $modelShouldBeUpdated->getAttribute('key'),
            'property' => $this->faker->uuid(),
        ]);
        $factory              = function (ChildrenTest_Type $type, ?Model $existing) use ($model): Model {
            return ($existing ?? clone $model)->forceFill([
                'id'       => $type->id ?? $this->faker->uuid(),
                'key'      => $type->key ?? null,
                'property' => $type->property ?? null,
            ]);
        };
        $keyer                = static function (Model|ChildrenTest_Type $object): string {
            $key = null;

            if ($object instanceof Model) {
                $key = new Key([
                    'key' => $object->getAttribute('key'),
                ]);
            } else {
                $key = new Key([
                    'key' => $object->key,
                ]);
            }

            return (string) $key;
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
        $actual   = $children->children($existing, $entries, $isReusable, $keyer, $factory);
        $expected = new Collection([
            tap(clone $model, static function (Model $model) use ($modelShouldBeReused, $typeShouldBeCreated): void {
                $model->setAttribute('id', $modelShouldBeReused->getKey());
                $model->setAttribute('key', $typeShouldBeCreated->key);
                $model->setAttribute('property', $typeShouldBeCreated->property);
            }),
            (clone $model)->forceFill([
                'id'       => $modelShouldBeUpdated->getKey(),
                'key'      => $modelShouldBeUpdated->getAttribute('key'),
                'property' => $typeShouldBeUpdated->property,
            ]),
        ]);

        self::assertInstanceOf($existing::class, $actual);
        self::assertEquals($expected, $actual);
    }
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ChildrenTest_Type extends Type {
    public string $id;
    public string $key;
    public string $property;
}
