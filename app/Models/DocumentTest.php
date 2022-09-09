<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Data\Product;
use Tests\TestCase;
use Tests\WithoutGlobalScopes;

/**
 * @internal
 * @coversDefaultClass \App\Models\Document
 */
class DocumentTest extends TestCase {
    use WithoutGlobalScopes;

    /**
     * @covers ::delete
     */
    public function testDelete(): void {
        $document = Document::factory()->create();

        DocumentEntry::factory(4)->create([
            'document_id' => $document,
            'product_id'  => Product::factory()->create(),
        ]);

        $document->delete();

        self::assertEquals(0, Document::query()->count());
        self::assertEquals(0, DocumentEntry::query()->count());
    }
}
