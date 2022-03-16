<?php declare(strict_types = 1);

namespace App\Services\Recalculator;

use App\Models\Customer;
use App\Models\Location;
use App\Models\Reseller;
use App\Queues;
use App\Services\Recalculator\Jobs\CustomerRecalculate;
use App\Services\Recalculator\Jobs\LocationRecalculate;
use App\Services\Recalculator\Jobs\Recalculate;
use App\Services\Recalculator\Jobs\ResellerRecalculate;
use App\Services\Service as BaseService;
use Illuminate\Database\Eloquent\Model;

use function array_keys;

class Service extends BaseService {
    /**
     * @var array<class-string<Model>,class-string<Recalculate>>
     */
    protected static array $recalculable = [
        Reseller::class => ResellerRecalculate::class,
        Customer::class => CustomerRecalculate::class,
        Location::class => LocationRecalculate::class,
    ];

    /**
     * @return array<class-string<Model>>
     */
    public function getRecalculableModels(): array {
        return array_keys(static::$recalculable);
    }

    /**
     * @param class-string<Model> $model
     *
     * @return class-string<Recalculate>|null
     */
    public function getRecalculableModelJob(string $model): ?string {
        return static::$recalculable[$model] ?? null;
    }

    public static function getDefaultQueue(): string {
        return Queues::RECALCULATOR;
    }
}
