<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use Tests\TestCase;

use function array_keys;
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
        $json = $this->getTestData()->json();

        self::assertIsArray($json);

        $keys       = array_keys($json);
        $actual     = new Company($json);
        $properties = Company::getPropertiesNames();

        self::assertEqualsCanonicalizing($keys, $properties);
        self::assertEqualsCanonicalizing($keys, array_keys($actual->getProperties()));
        self::assertCount(2, $actual->companyContactPersons);
        self::assertInstanceOf(CompanyContactPerson::class, reset($actual->companyContactPersons));
        self::assertCount(1, $actual->locations);
        self::assertInstanceOf(Location::class, reset($actual->locations));
        self::assertCount(1, $actual->assets);
        self::assertInstanceOf(ViewAsset::class, reset($actual->assets));
        self::assertInstanceOf(BrandingData::class, $actual->brandingData);
        self::assertInstanceOf(CompanyKpis::class, $actual->companyKpis);
        self::assertInstanceOf(CompanyKpis::class, $actual->companyResellerKpis[0] ?? null);
    }
}
