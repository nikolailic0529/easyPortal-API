<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use Tests\TestCase;

use function array_keys;
use function json_encode;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Schema\CompanyType
 */
class CompanyTypeTest extends TestCase {
    /**
     * @covers ::create
     */
    public function testCreate(): void {
        $json       = $this->getTestData()->json();
        $actual     = new CompanyType($json);
        $properties = CompanyType::getPropertiesNames();

        self::assertEquals(array_keys($json), $properties);
        self::assertJsonStringEqualsJsonString(
            json_encode($json),
            json_encode($actual),
        );
    }
}
