<?php declare(strict_types = 1);

namespace App\Services\Search\Queue\Jobs;

use App\Models\Document;

/**
 * Updates search index for Documents (Contracts/Quotes).
 *
 * @extends Indexer<Document>
 */
class DocumentsIndexer extends Indexer {
    public function displayName(): string {
        return 'ep-search-documents-indexer';
    }

    protected function getModel(): string {
        return Document::class;
    }
}
