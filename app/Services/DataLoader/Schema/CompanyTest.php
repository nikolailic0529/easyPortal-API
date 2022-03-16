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
        $actual     = new Company($json);
        $properties = Company::getPropertiesNames();

        self::assertEquals(array_keys($json), $properties);
        self::assertCount(2, $actual->companyContactPersons);
        self::assertInstanceOf(CompanyContactPerson::class, reset($actual->companyContactPersons));
        self::assertCount(1, $actual->companyTypes);
        self::assertInstanceOf(CompanyType::class, reset($actual->companyTypes));
        self::assertCount(1, $actual->locations);
        self::assertInstanceOf(Location::class, reset($actual->locations));
        self::assertCount(1, $actual->assets);
        self::assertInstanceOf(ViewAsset::class, reset($actual->assets));
        self::assertInstanceOf(BrandingData::class, $actual->brandingData);
        self::assertInstanceOf(CompanyKpis::class, $actual->companyKpis);
        self::assertInstanceOf(CompanyKpis::class, $actual->companyResellerKpis[0]);
        self::assertJsonStringEqualsJsonString(
            json_encode($json),
            json_encode($actual),
        );
    }
}
