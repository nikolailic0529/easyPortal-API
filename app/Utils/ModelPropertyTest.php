<?php declare(strict_types = 1);

namespace App\Utils;

use App\Models\Asset;
use App\Models\Oem;
use App\Models\Type;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Tests\WithoutOrganizationScope;

/**
 * @internal
 * @coversDefaultClass \App\Utils\ModelProperty
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
    }
}
