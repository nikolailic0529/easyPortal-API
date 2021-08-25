<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Document;
use App\Services\Search\Jobs\AssetsUpdaterCronJob;
use App\Services\Search\Jobs\CustomersUpdaterCronJob;
use App\Services\Search\Jobs\DocumentsUpdaterCronJob;
use App\Services\Service as BaseService;

use function array_keys;

class Service extends BaseService {
    /**
     * @var array<class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable>,\App\Services\Search\Jobs\UpdateIndexCronJob>
     */
    protected array $searchable = [
        Asset::class    => AssetsUpdaterCronJob::class,
        Customer::class => CustomersUpdaterCronJob::class,
        Document::class => DocumentsUpdaterCronJob::class,
    ];

    /**
     * @return array<class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable>>
     */
    public function getSearchableModels(): array {
        return array_keys($this->searchable);
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable> $model
     *
     * @return class-string<\App\Services\Search\Jobs\UpdateIndexCronJob>|null
     */
    public function getSearchableModelJob(string $model): ?string {
        return $this->searchable[$model] ?? null;
    }
}
