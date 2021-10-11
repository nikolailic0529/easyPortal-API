<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\SearchBy;

use App\Models\Asset;
use App\Models\Document;
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
        $this->assertFalse($metadata->isFulltextIndexExists(Asset::query(), 'id'));
        $this->assertTrue($metadata->isFulltextIndexExists(Document::query(), 'number'));
        $this->assertFalse($metadata->isFulltextIndexExists(Document::query(), 'id'));
    }
}
