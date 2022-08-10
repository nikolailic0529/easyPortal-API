<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use Tests\TestCase;

use function array_keys;
use function json_encode;
use function reset;

use const JSON_THROW_ON_ERROR;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Schema\DocumentEntry
 */
class DocumentEntryTest extends TestCase {
    /**
     * @covers ::create
     */
    public function testCreate(): void {
        $json = $this->getTestData()->json();

        self::assertIsArray($json);

        $actual     = new DocumentEntry($json);
        $properties = DocumentEntry::getPropertiesNames();

        self::assertEquals(array_keys($json), $properties);
        self::assertIsArray($actual->customFields);
        self::assertCount(3, $actual->customFields);
        self::assertInstanceOf(CustomField::class, reset($actual->customFields));
        self::assertJsonStringEqualsJsonString(
            json_encode($json, JSON_THROW_ON_ERROR),
            json_encode($actual, JSON_THROW_ON_ERROR),
        );
    }
}
