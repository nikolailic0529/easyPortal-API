<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use Tests\TestCase;

use function array_keys;
use function json_encode;
use function reset;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Schema\Company
 */
class CompanyTest extends TestCase {
    /**
     * @covers ::create
     */
    public function testCreate(): void {
        $json       = $this->getTestData()->json();
        $actual     = Company::create($json);
        $properties = Company::getPropertiesNames();

        $this->assertEquals(array_keys($json), $properties);
        $this->assertCount(2, $actual->companyContactPersons);
        $this->assertInstanceOf(CompanyContactPerson::class, reset($actual->companyContactPersons));
        $this->assertCount(1, $actual->companyTypes);
        $this->assertInstanceOf(CompanyType::class, reset($actual->companyTypes));
        $this->assertCount(1, $actual->locations);
        $this->assertInstanceOf(Location::class, reset($actual->locations));
        $this->assertJsonStringEqualsJsonString(
            json_encode($json),
            json_encode($actual),
        );
    }
}
