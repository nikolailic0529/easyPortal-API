<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Processors\Loader\Loaders\DocumentLoader;

/**
 * @extends ObjectSync<DocumentLoader>
 */
class DocumentSync extends ObjectSync {
    public function __invoke(DocumentLoader $loader): int {
        return $this->process($loader);
    }
}
