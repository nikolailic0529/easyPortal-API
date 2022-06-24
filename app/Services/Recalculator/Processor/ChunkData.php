<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Processor;

use App\Models\Asset;
use App\Models\Document;
use App\Utils\Eloquent\Callbacks\GetKey;
use App\Utils\Eloquent\Events\OnModelDeleted;
use App\Utils\Eloquent\Events\OnModelSaved;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

use function array_keys;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
class ChunkData implements OnModelSaved, OnModelDeleted {
    /**
     * @var array<string|int, bool>
     */
    private array $dirty = [];

    /**
     * @var TModel|null
     */
    private ?Model $model = null;

    /**
     * @var array<string|int>
     */
    private array $keys;

    /**
     * @var array<string, array<string, int>>
     */
    private array $assetsCount;

    /**
     * @var array<string, array<string, array<string, array<string, int>>>>
     */
    private array $assetsCountFor;

    /**
     * @var array<string, array<string, int>>
     */
    private array $quotesCount;

    /**
     * @var array<string, array<string, array<string, array<string, int>>>>
     */
    private array $quotesCountFor;

    /**
     * @var array<string, array<string, int>>
     */
    private array $contractsCount;

    /**
     * @var array<string, array<string, array<string, array<string, int>>>>
     */
    private array $contractsCountFor;

    /**
     * @param Collection<array-key,TModel>|array<TModel> $items
     */
    public function __construct(Collection|array $items) {
        $this->keys = (new Collection($items))->map(new GetKey())->all();
    }

    /**
     * @return TModel|null
     */
    public function getModel(): ?Model {
        return $this->model;
    }

    /**
     * @param TModel|null $model
     */
    public function setModel(mixed $model): void {
        $this->model = $model;
    }

    public function isDirty(): bool {
        return !!$this->dirty;
    }

    /**
     * @return array<string|int>
     */
    public function getDirtyKeys(): array {
        return array_keys($this->dirty);
    }

    /**
     * @return array<string|int>
     */
    public function getKeys(): array {
        return $this->keys;
    }

    /**
     * @return array<string,int>
     */
    protected function getAssetsCount(string $owner): array {
        if (!isset($this->assetsCount[$owner])) {
            // Calculate
            $data   = [];
            $keys   = $this->getKeys();
            $result = Asset::query()
                ->select([$owner, DB::raw('count(*) as count')])
                ->whereIn($owner, $keys)
                ->groupBy($owner)
                ->toBase()
                ->get();

            foreach ($result as $row) {
                /** @var stdClass $row */
                $ownerId        = (string) $row->{$owner};
                $data[$ownerId] = (int) $row->count;
            }

            // Save
            $this->assetsCount[$owner] = $data;
        }

        return $this->assetsCount[$owner];
    }

    /**
     * @return array<string, array<string, int>>
     */
    protected function getAssetsCountFor(string $owner, string $group, string $relation): array {
        if (!isset($this->assetsCountFor[$owner][$group])) {
            // Calculate
            $data   = [];
            $keys   = $this->getKeys();
            $result = Asset::query()
                ->select([$owner, $group, DB::raw('count(*) as count')])
                ->where(static function (Builder $builder) use ($group, $relation): void {
                    $builder
                        ->orWhereNull($group)
                        ->orWhereHasIn($relation);
                })
                ->whereIn($owner, $keys)
                ->groupBy($owner, $group)
                ->toBase()
                ->get();

            foreach ($result as $row) {
                /** @var stdClass $row */
                $ownerId                  = (string) $row->{$owner};
                $groupId                  = (string) $row->{$group};
                $data[$ownerId][$groupId] = (int) $row->count + ($data[$ownerId][$groupId] ?? 0);
            }

            // Save
            $this->assetsCountFor[$owner][$group] = $data;
        }

        return $this->assetsCountFor[$owner][$group];
    }

    /**
     * @return array<string,int>
     */
    protected function getQuotesCount(string $owner): array {
        if (!isset($this->quotesCount[$owner])) {
            // Calculate
            $data   = [];
            $keys   = $this->getKeys();
            $result = Document::query()
                ->queryQuotes()
                ->select([$owner, DB::raw('count(*) as count')])
                ->whereIn($owner, $keys)
                ->groupBy($owner)
                ->toBase()
                ->get();

            foreach ($result as $row) {
                /** @var stdClass $row */
                $ownerId        = (string) $row->{$owner};
                $data[$ownerId] = (int) $row->count;
            }

            // Save
            $this->quotesCount[$owner] = $data;
        }

        return $this->quotesCount[$owner];
    }

    /**
     * @return array<string, array<string, int>>
     */
    protected function getQuotesCountFor(string $owner, string $group, string $relation): array {
        if (!isset($this->quotesCountFor[$owner][$group])) {
            // Calculate
            $data   = [];
            $keys   = $this->getKeys();
            $result = Document::query()
                ->queryQuotes()
                ->select([$owner, $group, DB::raw('count(*) as count')])
                ->where(static function (Builder $builder) use ($group, $relation): void {
                    $builder
                        ->orWhereNull($group)
                        ->orWhereHasIn($relation);
                })
                ->whereIn($owner, $keys)
                ->groupBy($owner, $group)
                ->toBase()
                ->get();

            foreach ($result as $row) {
                /** @var stdClass $row */
                $ownerId                  = (string) $row->{$owner};
                $groupId                  = (string) $row->{$group};
                $data[$ownerId][$groupId] = (int) $row->count + ($data[$ownerId][$groupId] ?? 0);
            }

            // Save
            $this->quotesCountFor[$owner][$group] = $data;
        }

        return $this->quotesCountFor[$owner][$group];
    }

    /**
     * @return array<string,int>
     */
    protected function getContractsCount(string $owner): array {
        if (!isset($this->contractsCount[$owner])) {
            // Calculate
            $data   = [];
            $keys   = $this->getKeys();
            $result = Document::query()
                ->queryContracts()
                ->select([$owner, DB::raw('count(*) as count')])
                ->whereIn($owner, $keys)
                ->groupBy($owner)
                ->toBase()
                ->get();

            foreach ($result as $row) {
                /** @var stdClass $row */
                $ownerId        = (string) $row->{$owner};
                $data[$ownerId] = (int) $row->count;
            }

            // Save
            $this->contractsCount[$owner] = $data;
        }

        return $this->contractsCount[$owner];
    }

    /**
     * @return array<string, array<string, int>>
     */
    protected function getContractsCountFor(string $owner, string $group, string $relation): array {
        if (!isset($this->contractsCountFor[$owner][$group])) {
            // Calculate
            $data   = [];
            $keys   = $this->getKeys();
            $result = Document::query()
                ->queryContracts()
                ->select([$owner, $group, DB::raw('count(*) as count')])
                ->where(static function (Builder $builder) use ($group, $relation): void {
                    $builder
                        ->orWhereNull($group)
                        ->orWhereHasIn($relation);
                })
                ->whereIn($owner, $keys)
                ->groupBy($owner, $group)
                ->toBase()
                ->get();

            foreach ($result as $row) {
                /** @var stdClass $row */
                $ownerId                  = (string) $row->{$owner};
                $groupId                  = (string) $row->{$group};
                $data[$ownerId][$groupId] = (int) $row->count + ($data[$ownerId][$groupId] ?? 0);
            }

            // Save
            $this->contractsCountFor[$owner][$group] = $data;
        }

        return $this->contractsCountFor[$owner][$group];
    }

    public function modelSaved(Model $model): void {
        $key     = $this->getModel()?->getKey();
        $isDirty = $this->getModel()?->isDirty();

        if ($key && $isDirty) {
            $this->dirty[$key] = true;
        }
    }

    public function modelDeleted(Model $model): void {
        $this->modelSaved($model);
    }
}
