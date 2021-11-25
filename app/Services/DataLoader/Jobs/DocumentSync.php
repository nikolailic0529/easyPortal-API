<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\DataLoader\Commands\UpdateDocument;
use Illuminate\Contracts\Console\Kernel;

/**
 * Syncs Document.
 *
 * @see \App\Services\DataLoader\Commands\UpdateDocument
 */
class DocumentSync extends Sync {
    public function displayName(): string {
        return 'ep-data-loader-document-sync';
    }


    public function init(string $id): static {
        $this->objectId  = $id;
        $this->arguments = [];

        $this->initialized();

        return $this;
    }

    public function __invoke(Kernel $kernel): void {
        $this->checkCommandResult(
            $kernel->call(UpdateDocument::class, [
                'id' => $this->objectId,
            ]),
        );
    }
}
