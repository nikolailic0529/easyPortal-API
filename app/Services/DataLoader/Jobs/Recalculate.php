<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Asset;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Services\Queue\Queues;
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
                'queue' => Queues::DATA_LOADER_RECALCULATE,
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
    protected function calculateAssetsBy(string $property, array $keys): array {
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
    protected function calculateAssetsByLocation(string $property, array $keys): array {
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
            $i = $row->{$property};
            $l = (string) $row->location_id;

            $data[$i][$l] = (int) $row->count + ($data[$i][$l] ?? 0);
        }

        return $data;
    }
}
