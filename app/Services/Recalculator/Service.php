<?php declare(strict_types = 1);

namespace App\Services\Recalculator;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Reseller;
use App\Queues;
use App\Services\Recalculator\Processor\ChunkData;
use App\Services\Recalculator\Processor\Processor;
use App\Services\Recalculator\Processor\Processors\AssetsProcessor;
use App\Services\Recalculator\Processor\Processors\CustomersProcessor;
use App\Services\Recalculator\Processor\Processors\LocationsProcessor;
use App\Services\Recalculator\Processor\Processors\ResellersProcessor;
use App\Services\Recalculator\Queue\Jobs\AssetsRecalculator;
use App\Services\Recalculator\Queue\Jobs\CustomersRecalculator;
use App\Services\Recalculator\Queue\Jobs\LocationsRecalculator;
use App\Services\Recalculator\Queue\Jobs\Recalculator;
use App\Services\Recalculator\Queue\Jobs\ResellersRecalculator;
use App\Services\Service as BaseService;
use App\Utils\Processor\EloquentState;
use Illuminate\Database\Eloquent\Model;

use function array_keys;

class Service extends BaseService {
    /**
     * @var array<class-string<Model>,class-string<Recalculator<Model>>>
     */
    protected static array $recalculable = [
        Asset::class    => AssetsRecalculator::class,
        Reseller::class => ResellersRecalculator::class,
        Customer::class => CustomersRecalculator::class,
        Location::class => LocationsRecalculator::class,
    ];

    /**
     * @var array<class-string<Model>, class-string<Processor<Model, ChunkData<Model>, EloquentState<Model>>>>
     */
    protected static array $processors = [
        Asset::class    => AssetsProcessor::class,
        Reseller::class => ResellersProcessor::class,
        Customer::class => CustomersProcessor::class,
        Location::class => LocationsProcessor::class,
    ];

    /**
     * @param class-string<Model> $model
     */
    public function isRecalculableModel(string $model): bool {
        return isset(static::$recalculable[$model]);
    }

    /**
     * @return array<class-string<Model>>
     */
    public function getRecalculableModels(): array {
        return array_keys(static::$recalculable);
    }

    /**
     * @param class-string<Model> $model
     *
     * @return class-string<Recalculator<Model>>|null
     */
    public function getRecalculableModelJob(string $model): ?string {
        return static::$recalculable[$model] ?? null;
    }

    /**
     * @param class-string<Model> $model
     *
     * @return class-string<Processor<Model, ChunkData<Model>, EloquentState<Model>>>|null
     */
    public function getRecalculableModelProcessor(string $model): ?string {
        return static::$processors[$model] ?? null;
    }

    public static function getDefaultQueue(): string {
        return Queues::RECALCULATOR;
    }
}
