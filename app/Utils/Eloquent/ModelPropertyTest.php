<?php declare(strict_types = 1);

namespace App\Utils\Eloquent;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Oem;
use App\Models\Type;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use LastDragon_ru\LaraASP\Eloquent\Exceptions\PropertyIsNotRelation;
use Tests\TestCase;
use Tests\WithoutOrganizationScope;

/**
 * @internal
 * @coversDefaultClass \App\Utils\Eloquent\ModelProperty
 */
class ModelPropertyTest extends TestCase {
    use WithoutOrganizationScope;

    /**
     * @covers ::__construct
     * @covers ::getName
     * @covers ::getPath
     * @covers ::isRelation
     * @covers ::isAttribute
     * @covers ::getRelationName
     * @covers ::getRelationPath
     */
    public function testGetters(): void {
        // Without relation
        $a = new ModelProperty('a');

        $this->assertEquals('a', $a->getName());
        $this->assertEquals(['a'], $a->getPath());
        $this->assertTrue($a->isAttribute());
        $this->assertFalse($a->isRelation());
        $this->assertNull($a->getRelationPath());
        $this->assertNull($a->getRelationName());

        // With
        $b = new ModelProperty('a.b.c');

        $this->assertEquals('c', $b->getName());
        $this->assertEquals(['a', 'b', 'c'], $b->getPath());
        $this->assertTrue($b->isRelation());
        $this->assertFalse($b->isAttribute());
        $this->assertEquals(['a', 'b'], $b->getRelationPath());
        $this->assertEquals('a.b', $b->getRelationName());
    }

    /**
     * @covers ::getValue
     */
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
        $this->assertEquals($oem->key, (new ModelProperty('key'))->getValue($oem));
        $this->assertEquals($oem->getKey(), (new ModelProperty('oem_id'))->getValue($assetA));

        // Relations
        $this->assertEquals(
            $oem->getKey(),
            (new ModelProperty('oem.id'))->getValue($assetA),
        );
        $this->assertEqualsCanonicalizing(
            new Collection([$assetA->getKey(), $assetB->getKey()]),
            (new ModelProperty('assets.id'))->getValue($oem),
        );
        $this->assertEquals(
            new Collection([$type->getKey()]),
            (new ModelProperty('assets.type.id'))->getValue($oem),
        );

        // Null
        $this->assertNull(
            (new ModelProperty('unknown.id'))->getValue($oem),
        );
        $this->assertNull(
            (new ModelProperty('assets.unknown.id'))->getValue($oem),
        );
    }

    /**
     * @covers ::getRelation
     */
    public function testGetRelationForModel(): void {
        $model    = new Customer();
        $property = new ModelProperty('headquarter.id');

        $this->assertInstanceOf(HasOne::class, $property->getRelation($model));
    }

    /**
     * @covers ::getRelation
     */
    public function testGetRelationForModelNotRelation(): void {
        $model    = new Customer();
        $property = new ModelProperty('id');

        $this->expectExceptionObject(new PropertyIsNotRelation($model, $property->getName()));

        $property->getRelation($model);
    }

    /**
     * @covers ::getRelation
     */
    public function testGetRelationForBuilder(): void {
        $builder  = Customer::query();
        $property = new ModelProperty('headquarter.id');

        $this->assertInstanceOf(HasOne::class, $property->getRelation($builder));
    }

    /**
     * @covers ::getRelation
     */
    public function testGetRelationForBuilderNotRelation(): void {
        $model    = new Customer();
        $builder  = $model::query();
        $property = new ModelProperty('id');

        $this->expectExceptionObject(new PropertyIsNotRelation($model, $property->getName()));

        $property->getRelation($builder);
    }
}
