<?php declare(strict_types = 1);

namespace App\Services\Search\Jobs\Cron;

use App\Models\Asset;

/**
 * Updates search index for Assets.
 */
class AssetsIndexer extends Indexer {
    public function displayName(): string {
        return 'ep-search-assets-indexer';
    }

    protected function getModel(): string {
        return Asset::class;
    }
}
