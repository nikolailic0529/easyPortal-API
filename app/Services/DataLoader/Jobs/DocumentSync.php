<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\DataLoader\Commands\UpdateDocument;
use App\Services\DataLoader\Jobs\Concerns\CommandOptions;
use Illuminate\Contracts\Console\Kernel;

/**
 * Syncs Document.
 *
 * @see \App\Services\DataLoader\Commands\UpdateDocument
 */
class DocumentSync extends Sync {
    use CommandOptions;

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
        $kernel->call(UpdateDocument::class, [
            'id' => $this->objectId,
        ]);
    }
}
