<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Models\Document;
use App\Services\DataLoader\Schema\DocumentEntry;
use Throwable;

use function sprintf;

class FailedToProcessDocumentEntryNoAsset extends FailedToProcessObject {
    public function __construct(
        protected Document $document,
        protected DocumentEntry $entry,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Failed to process DocumentEntry for Document `%s`: `assetId` is null.',
            $this->document->getKey(),
        ), $previous);
    }
}
