<?php declare(strict_types = 1);

namespace App\GraphQL\Extensions\LaraAsp\SearchBy;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Data\Product;
use App\Models\Document;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\GraphQL\Extensions\LaraAsp\SearchBy\Metadata
 */
class MetadataTest extends TestCase {
    public function testIsFulltextIndexExists(): void {
        $metadata = new Metadata();

        self::assertTrue($metadata->isFulltextIndexExists(Asset::query(), 'serial_number'));
        self::assertTrue($metadata->isFulltextIndexExists(Asset::query(), (new Asset())->getTable().'.serial_number'));
        self::assertFalse($metadata->isFulltextIndexExists(Asset::query(), 'id'));

        self::assertTrue($metadata->isFulltextIndexExists(Document::query(), 'number'));
        self::assertFalse($metadata->isFulltextIndexExists(Document::query(), 'id'));

        self::assertTrue($metadata->isFulltextIndexExists(Customer::query(), 'name'));
        self::assertFalse($metadata->isFulltextIndexExists(Customer::query(), 'id'));

        self::assertTrue($metadata->isFulltextIndexExists(Product::query(), 'name'));
        self::assertFalse($metadata->isFulltextIndexExists(Product::query(), 'id'));
    }
}
