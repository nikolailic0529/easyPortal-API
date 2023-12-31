<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Document;
use App\Queues;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Queue\Jobs\AssetsIndexer;
use App\Services\Search\Queue\Jobs\CustomersIndexer;
use App\Services\Search\Queue\Jobs\DocumentsIndexer;
use App\Services\Search\Queue\Jobs\Indexer;
use App\Services\Service as BaseService;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

use function array_keys;

class Service extends BaseService {
    /**
     * @var array<class-string<Model&Searchable>,class-string<Indexer<Model&Searchable>>>
     */
    protected static array $searchable = [
        Customer::class => CustomersIndexer::class,
        Document::class => DocumentsIndexer::class,
        Asset::class    => AssetsIndexer::class,
    ];

    /**
     * @param class-string<Model> $model
     */
    public function isSearchableModel(string $model): bool {
        return isset(static::$searchable[$model]);
    }

    /**
     * @return array<class-string<Model&Searchable>>
     */
    public function getSearchableModels(): array {
        return array_keys(static::$searchable);
    }

    /**
     * @param class-string<Model> $model
     *
     * @return class-string<Indexer<Model&Searchable>>|null
     */
    public function getSearchableModelJob(string $model): ?string {
        return static::$searchable[$model] ?? null;
    }

    /**
     * @template T
     *
     * @param Closure(): T $closure
     *
     * @return T
     */
    public static function callWithoutIndexing(Closure $closure): mixed {
        $previous = (new Collection(static::$searchable))
            ->map(static function (mixed $value, string $model): bool {
                $enabled = $model::isSearchSyncingEnabled();

                $model::disableSearchSyncing();

                return $enabled;
            });

        try {
            return $closure();
        } finally {
            foreach ($previous as $model => $enabled) {
                if ($enabled) {
                    $model::enableSearchSyncing();
                }
            }
        }
    }

    public static function getDefaultQueue(): string {
        return Queues::SEARCH;
    }
}
