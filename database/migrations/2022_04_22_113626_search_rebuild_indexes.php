<?php declare(strict_types = 1);

use App\Models\Asset;
use App\Services\Search\Migrations\IndexesRebuild;

return new class() extends IndexesRebuild {
    /**
     * @inheritdoc
     */
    protected function getSearchableModels(): array {
        return [
            Asset::class,
        ];
    }
};
