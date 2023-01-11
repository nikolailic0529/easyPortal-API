<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use Tests\TestCase;

use function array_keys;

/**
 * @internal
 * @covers \App\Services\DataLoader\Schema\Types\ViewDocument
 */
class ViewDocumentTest extends TestCase {
    public function testCreate(): void {
        $json = $this->getTestData()->json();

        self::assertIsArray($json);

        $keys       = array_keys($json);
        $actual     = new ViewDocument($json);
        $properties = ViewDocument::getPropertiesNames();

        self::assertEquals($actual->id, $actual->getKey());
        self::assertEqualsCanonicalizing($keys, $properties);
        self::assertEqualsCanonicalizing($keys, array_keys($actual->getProperties()));
    }
}
