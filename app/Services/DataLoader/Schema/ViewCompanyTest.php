<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use Tests\TestCase;

use function array_keys;
use function json_encode;
use function reset;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Schema\ViewCompany
 */
class ViewCompanyTest extends TestCase {
    /**
     * @covers ::create
     */
    public function testCreate(): void {
        $json       = $this->getTestData()->json();
        $actual     = new ViewCompany($json);
        $properties = ViewCompany::getPropertiesNames();

        self::assertEquals(array_keys($json), $properties);
        self::assertCount(2, $actual->companyContactPersons);
        self::assertInstanceOf(CompanyContactPerson::class, reset($actual->companyContactPersons));
        self::assertCount(1, $actual->companyTypes);
        self::assertInstanceOf(CompanyType::class, reset($actual->companyTypes));
        self::assertCount(1, $actual->locations);
        self::assertInstanceOf(Location::class, reset($actual->locations));
        self::assertJsonStringEqualsJsonString(
            json_encode($json),
            json_encode($actual),
        );
    }
}
