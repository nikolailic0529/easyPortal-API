<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Models\Document;
use App\Services\DataLoader\Schema\Types\DocumentEntry;
use Psr\Log\LogLevel;
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

        $this->setLevel(LogLevel::NOTICE);
    }

    public function getDocument(): Document {
        return $this->document;
    }

    public function getEntry(): DocumentEntry {
        return $this->entry;
    }
}
