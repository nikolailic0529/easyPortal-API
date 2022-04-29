<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers;

use App\Models\Document;
use App\Services\DataLoader\Importer\IteratorIterator;
use App\Services\DataLoader\Schema\Document as SchemaDocument;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Processor\State;
use LogicException;

use function is_object;

/**
 * @extends DocumentsImporter<DocumentsImporterState>
 */
class DocumentsIteratorImporter extends DocumentsImporter {
    /**
     * @var ObjectIterator<SchemaDocument>
     */
    private ObjectIterator $iterator;

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    /**
     * @param ObjectIterator<string|Document> $iterator
     */
    public function setIterator(ObjectIterator $iterator): static {
        $this->iterator = new IteratorIterator(
            $this->getExceptionHandler(),
            $iterator,
            function (Document|string $document): ?SchemaDocument {
                $document = is_object($document) ? $document->getKey() : $document;
                $document = $this->getClient()->getDocumentById($document);

                return $document;
            },
        );

        return $this;
    }
    // </editor-fold>

    // <editor-fold desc="Importer">
    // =========================================================================
    protected function getTotal(State $state): ?int {
        return null;
    }

    protected function getIterator(State $state): ObjectIterator {
        if ($state->from !== null) {
            throw new LogicException('Parameter `from` is not supported.');
        }

        return $this->iterator;
    }
    // </editor-fold>
}
