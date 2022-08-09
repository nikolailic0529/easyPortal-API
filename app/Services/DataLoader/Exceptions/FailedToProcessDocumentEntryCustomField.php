<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Models\Document;
use App\Services\DataLoader\Schema\CustomField;
use App\Services\DataLoader\Schema\DocumentEntry;
use Throwable;

use function sprintf;

class FailedToProcessDocumentEntryCustomField extends FailedToProcessObject {
    public function __construct(
        protected Document $document,
        protected DocumentEntry $entry,
        protected CustomField $field,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Failed to process CustomField for Document `%s`.',
            $this->document->getKey(),
        ), $previous);

        $this->setContext([
            'entry' => $this->entry,
            'field' => $this->field,
        ]);
    }
}
