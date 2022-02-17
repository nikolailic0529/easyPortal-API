<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Jobs;

use App\Models\Asset;
use App\Queues;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Services\Queue\Job;
use App\Utils\Eloquent\Callbacks\GetKey;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\SmartSave\BatchSave;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Queue\Contracts\Initializable;

use function sprintf;

/**
 * @template T of \App\Utils\Eloquent\Model
 */
abstract class Recalculate extends Job implements Initializable {
    /**
     * @var array<string>
     */
    protected array $keys;

    /**
     * @return array<mixed>
     */
    public function getQueueConfig(): array {
        return [
                'queue' => Queues::RECALCULATOR,
            ] + parent::getQueueConfig();
    }

    /**
     * @return array<string>
     */
    public function getKeys(): array {
        return $this->keys;
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Utils\Eloquent\Model> $models
     */
    public function setModels(Collection $models): static {
        // Valid?
        if ($models->isEmpty()) {
            throw new InvalidArgumentException('The `$models` cannot be empty.');
        }

        $expected = $this->getModel();
        $actual   = $models->first();

        if (!($actual instanceof $expected)) {
            throw new InvalidArgumentException(sprintf(
                'The `$models` must contain `%s` models, but it is contain `%s`.',
                $expected::class,
                $actual::class,
            ));
        }

        // Initialize
        $this->keys = (new Collection($models))->map(new GetKey())->unique()->sort()->values()->all();

        $this->initialized();

        // Return
        return $this;
    }

    /**
     * @return T
     */
    abstract public function getModel(): Model;

    abstract protected function process(): void;

    public function __invoke(): void {
        GlobalScopes::callWithoutGlobalScope(OwnedByOrganizationScope::class, function (): void {
            BatchSave::enable(function (): void {
                $this->process();
            });
        });
    }

    /**
     * @param array<string> $keys
     *
     * @return array<string,int>
     */
    protected function calculateAssetsFor(string $property, array $keys): array {
        $data   = [];
        $result = Asset::query()
            ->select([$property, DB::raw('count(*) as count')])
            ->whereIn($property, $keys)
            ->groupBy($property)
            ->toBase()
            ->get();

        foreach ($result as $row) {
            /** @var \stdClass $row */
            $data[$row->{$property}] = (int) $row->count;
        }

        return $data;
    }

    /**
     * @param array<string> $keys
     *
     * @return array<string,array<string, int>>
     */
    protected function calculateAssetsByLocationFor(string $property, array $keys): array {
        $data   = [];
        $result = Asset::query()
            ->select([$property, 'location_id', DB::raw('count(*) as count')])
            ->whereIn($property, $keys)
            ->where(static function (Builder $builder): void {
                $builder
                    ->orWhereNull('location_id')
                    ->orWhereHasIn('location');
            })
            ->groupBy($property, 'location_id')
            ->toBase()
            ->get();

        foreach ($result as $row) {
            /** @var \stdClass $row */
            $i = (string) $row->{$property};
            $l = (string) $row->location_id;

            $data[$i][$l] = (int) $row->count + ($data[$i][$l] ?? 0);
        }

        return $data;
    }

    /**
     * @param array<string> $keys
     *
     * @return array<string,array<string, int>>
     */
    protected function calculateAssetsByCustomerFor(string $property, array $keys): array {
        $data   = [];
        $result = Asset::query()
            ->select([$property, 'customer_id', DB::raw('count(*) as count')])
            ->whereIn($property, $keys)
            ->where(static function (Builder $builder): void {
                $builder
                    ->orWhereNull('customer_id')
                    ->orWhereHasIn('customer');
            })
            ->groupBy($property, 'customer_id')
            ->toBase()
            ->get();

        foreach ($result as $row) {
            /** @var \stdClass $row */
            $i = (string) $row->{$property};
            $c = (string) $row->customer_id;

            $data[$i][$c] = (int) $row->count + ($data[$i][$c] ?? 0);
        }

        return $data;
    }

    /**
     * @param array<string> $keys
     *
     * @return array<string,array<string, int>>
     */
    protected function calculateAssetsByResellerFor(string $property, array $keys): array {
        $data   = [];
        $result = Asset::query()
            ->select([$property, 'reseller_id', DB::raw('count(*) as count')])
            ->whereIn($property, $keys)
            ->where(static function (Builder $builder): void {
                $builder
                    ->orWhereNull('reseller_id')
                    ->orWhereHasIn('reseller');
            })
            ->groupBy($property, 'reseller_id')
            ->toBase()
            ->get();

        foreach ($result as $row) {
            /** @var \stdClass $row */
            $i = (string) $row->{$property};
            $r = (string) $row->reseller_id;

            $data[$i][$r] = (int) $row->count + ($data[$i][$r] ?? 0);
        }

        return $data;
    }
}
