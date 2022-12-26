<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Models\Document;
use App\Services\DataLoader\Schema\Types\DocumentEntry;
use Throwable;

use function sprintf;

class FailedToProcessDocumentEntry extends FailedToProcessObject {
    public function __construct(
        protected Document $document,
        protected DocumentEntry $entry,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Failed to process DocumentEntry for Document `%s`.',
            $this->document->getKey(),
        ), $previous);

        $this->setContext([
            'entry' => $this->entry,
        ]);
    }
}
