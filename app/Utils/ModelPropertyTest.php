<?php declare(strict_types = 1);

namespace App\Utils;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Utils\ModelProperty
 */
class ModelPropertyTest extends TestCase {
    /**
     * @covers ::__construct
     * @covers ::getName
     * @covers ::getPath
     * @covers ::isRelation
     * @covers ::getRelation
     */
    public function testAll(): void {
        // Without relation
        $a = new ModelProperty('a');

        $this->assertEquals('a', $a->getName());
        $this->assertFalse($a->isRelation());
        $this->assertNull($a->getPath());
        $this->assertNull($a->getRelation());

        // With
        $b = new ModelProperty('a.b.c');

        $this->assertEquals('c', $b->getName());
        $this->assertTrue($b->isRelation());
        $this->assertEquals(['a', 'b'], $b->getPath());
        $this->assertEquals('a.b', $b->getRelation());
    }
}
