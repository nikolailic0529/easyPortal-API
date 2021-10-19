<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\DataLoader\Commands\UpdateDocument;
use App\Services\DataLoader\Jobs\Concerns\CommandOptions;
use Illuminate\Contracts\Console\Kernel;

/**
 * Updates Document.
 *
 * @see \App\Services\DataLoader\Commands\UpdateDocument
 */
class DocumentUpdate extends Sync {
    use CommandOptions;

    protected string $documentId;

    public function getDocumentId(): string {
        return $this->documentId;
    }

    public function displayName(): string {
        return 'ep-data-loader-document-update';
    }

    public function uniqueId(): string {
        return $this->documentId;
    }

    public function init(string $documentId): static {
        $this->documentId = $documentId;

        $this->initialized();

        return $this;
    }

    public function __invoke(Kernel $kernel): void {
        $kernel->call(UpdateDocument::class, [
            'id' => $this->getDocumentId(),
        ]);
    }
}
