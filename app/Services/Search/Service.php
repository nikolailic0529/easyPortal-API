<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Document;
use App\Services\Search\Jobs\AssetsUpdaterCronJob;
use App\Services\Search\Jobs\CustomersUpdaterCronJob;
use App\Services\Search\Jobs\DocumentsUpdaterCronJob;
use App\Services\Service as BaseService;
use Closure;
use Illuminate\Support\Collection;

use function array_keys;

class Service extends BaseService {
    /**
     * @var array<class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable>,\App\Services\Search\Jobs\UpdateIndexCronJob>
     */
    protected static array $searchable = [
        Asset::class    => AssetsUpdaterCronJob::class,
        Customer::class => CustomersUpdaterCronJob::class,
        Document::class => DocumentsUpdaterCronJob::class,
    ];

    /**
     * @return array<class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable>>
     */
    public function getSearchableModels(): array {
        return array_keys(static::$searchable);
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable> $model
     *
     * @return class-string<\App\Services\Search\Jobs\UpdateIndexCronJob>|null
     */
    public function getSearchableModelJob(string $model): ?string {
        return static::$searchable[$model] ?? null;
    }

    /**
     * @template T
     *
     * @param \Closure(): T $closure
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
}
