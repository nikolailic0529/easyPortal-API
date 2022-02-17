<?php declare(strict_types = 1);

namespace App\Services\Recalculator;

use App\Models\Customer;
use App\Models\Location;
use App\Models\Reseller;
use App\Services\Recalculator\Jobs\CustomersRecalculate;
use App\Services\Recalculator\Jobs\LocationsRecalculate;
use App\Services\Recalculator\Jobs\ResellersRecalculate;
use App\Services\Service as BaseService;

use function array_keys;

class Service extends BaseService {
    /**
     * @var array<class-string<\Illuminate\Database\Eloquent\Model>,class-string<\App\Services\Recalculator\Jobs\Recalculate>>
     */
    protected static array $recalculable = [
        Reseller::class => ResellersRecalculate::class,
        Customer::class => CustomersRecalculate::class,
        Location::class => LocationsRecalculate::class,
    ];

    /**
     * @return array<class-string<\Illuminate\Database\Eloquent\Model>>
     */
    public function getRecalculableModels(): array {
        return array_keys(static::$recalculable);
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $model
     *
     * @return class-string<\App\Services\Recalculator\Jobs\Recalculate>|null
     */
    public function getRecalculableModelJob(string $model): ?string {
        return static::$recalculable[$model] ?? null;
    }
}
