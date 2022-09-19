<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Loader\Loaders\DocumentLoader;
use App\Services\I18n\Formatter;

/**
 * @extends ObjectSync<DocumentLoader>
 */
class DocumentSync extends ObjectSync {
    public function __invoke(Formatter $formatter, DocumentLoader $loader): int {
        return $this->process($formatter, $loader);
    }
}
