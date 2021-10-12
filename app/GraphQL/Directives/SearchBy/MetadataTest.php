<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\SearchBy;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Product;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\SearchBy\Metadata
 */
class MetadataTest extends TestCase {
    /**
     * @covers ::isFulltextIndexExists
     */
    public function testIsFulltextIndexExists(): void {
        $metadata = new Metadata();

        $this->assertTrue($metadata->isFulltextIndexExists(Asset::query(), 'serial_number'));
        $this->assertTrue($metadata->isFulltextIndexExists(Asset::query(), (new Asset())->getTable().'.serial_number'));
        $this->assertFalse($metadata->isFulltextIndexExists(Asset::query(), 'id'));

        $this->assertTrue($metadata->isFulltextIndexExists(Document::query(), 'number'));
        $this->assertFalse($metadata->isFulltextIndexExists(Document::query(), 'id'));

        $this->assertTrue($metadata->isFulltextIndexExists(Customer::query(), 'name'));
        $this->assertFalse($metadata->isFulltextIndexExists(Customer::query(), 'id'));

        $this->assertTrue($metadata->isFulltextIndexExists(Product::query(), 'name'));
        $this->assertFalse($metadata->isFulltextIndexExists(Product::query(), 'id'));
    }
}
