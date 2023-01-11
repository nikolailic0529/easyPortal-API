<?php declare(strict_types = 1);

namespace App\Utils\Eloquent;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Data\Oem;
use App\Models\Data\Type;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use LastDragon_ru\LaraASP\Eloquent\Exceptions\PropertyIsNotRelation;
use Tests\TestCase;
use Tests\WithoutGlobalScopes;

/**
 * @internal
 * @covers \App\Utils\Eloquent\ModelProperty
 */
class ModelPropertyTest extends TestCase {
    use WithoutGlobalScopes;

    public function testGetters(): void {
        // Without relation
        $a = new ModelProperty('a');

        self::assertEquals('a', $a->getName());
        self::assertEquals('a', $a->getFullName());
        self::assertEquals(['a'], $a->getPath());
        self::assertTrue($a->isAttribute());
        self::assertFalse($a->isRelation());
        self::assertNull($a->getRelationPath());
        self::assertNull($a->getRelationName());

        // With
        $b = new ModelProperty('a.b.c');

        self::assertEquals('c', $b->getName());
        self::assertEquals('a.b.c', $b->getFullName());
        self::assertEquals(['a', 'b', 'c'], $b->getPath());
        self::assertTrue($b->isRelation());
        self::assertFalse($b->isAttribute());
        self::assertEquals(['a', 'b'], $b->getRelationPath());
        self::assertEquals('a.b', $b->getRelationName());
    }

    public function testGetValue(): void {
        // Prepare
        $oem    = Oem::factory()->create();
        $type   = Type::factory()->create();
        $assetA = Asset::factory()->create([
            'oem_id'  => $oem,
            'type_id' => $type,
        ]);
        $assetB = Asset::factory()->create([
            'oem_id'  => $oem,
            'type_id' => $type,
        ]);

        // Simple
        self::assertEquals($oem->key, (new ModelProperty('key'))->getValue($oem));
        self::assertEquals($oem->getKey(), (new ModelProperty('oem_id'))->getValue($assetA));

        // Relations
        self::assertEquals(
            $oem->getKey(),
            (new ModelProperty('oem.id'))->getValue($assetA),
        );
        self::assertEqualsCanonicalizing(
            new Collection([$assetA->getKey(), $assetB->getKey()]),
            (new ModelProperty('assets.id'))->getValue($oem),
        );
        self::assertEquals(
            new Collection([$type->getKey()]),
            (new ModelProperty('assets.type.id'))->getValue($oem),
        );

        // Null
        self::assertNull(
            (new ModelProperty('unknown.id'))->getValue($oem),
        );
        self::assertNull(
            (new ModelProperty('assets.unknown.id'))->getValue($oem),
        );
    }

    public function testGetRelationForModel(): void {
        $model    = new Customer();
        $property = new ModelProperty('headquarter.id');

        self::assertInstanceOf(HasOne::class, $property->getRelation($model));
    }

    public function testGetRelationForModelNotRelation(): void {
        $model    = new Customer();
        $property = new ModelProperty('id');

        self::expectExceptionObject(new PropertyIsNotRelation($model, $property->getName()));

        $property->getRelation($model);
    }

    public function testGetRelationForBuilder(): void {
        $builder  = Customer::query();
        $property = new ModelProperty('headquarter.id');

        self::assertInstanceOf(HasOne::class, $property->getRelation($builder));
    }

    public function testGetRelationForBuilderNotRelation(): void {
        $model    = new Customer();
        $builder  = $model::query();
        $property = new ModelProperty('id');

        self::expectExceptionObject(new PropertyIsNotRelation($model, $property->getName()));

        $property->getRelation($builder);
    }
}
