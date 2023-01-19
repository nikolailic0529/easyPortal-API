<?php declare(strict_types = 1);

use App\Models\Document;
use App\Services\Search\Migrations\IndexesRebuild;

return new class() extends IndexesRebuild {
    /**
     * @inheritDoc
     */
    protected function getSearchableModels(): array {
        return [
            Document::class,
        ];
    }
};
