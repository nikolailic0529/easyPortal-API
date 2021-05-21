<?php declare(strict_types = 1);

namespace App\Models;

use Tests\TestCase;
use Tests\WithoutOrganizationScope;

/**
 * @internal
 * @coversDefaultClass \App\Models\Document
 */
class DocumentTest extends TestCase {
    use WithoutOrganizationScope;

    /**
     * @covers ::delete
     */
    public function testDelete(): void {
        $document = Document::factory()->create();

        DocumentEntry::factory(4)->create([
            'document_id' => $document,
        ]);

        $document->delete();

        $this->assertEquals(0, Document::query()->count());
        $this->assertEquals(0, DocumentEntry::query()->count());
    }
}
