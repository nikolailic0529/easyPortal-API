<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Processor;

use App\Models\Asset;
use App\Utils\Eloquent\Callbacks\GetKey;
use App\Utils\Eloquent\Events\OnModelDeleted;
use App\Utils\Eloquent\Events\OnModelSaved;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
class ChunkData implements OnModelSaved, OnModelDeleted {
    private bool $dirty = false;

    /**
     * @var array<string>
     */
    private array $keys;

    /**
     * @var array<string, array<string, int>>
     */
    private array $assetsCount;

    /**
     * @var array<string, array<string, array<string, int>>>
     */
    private array $assetsCountFor;

    /**
     * @param Collection<array-key,TModel>|array<TModel> $items
     */
    public function __construct(Collection|array $items) {
        $this->keys = (new Collection($items))->map(new GetKey())->all();
    }

    public function isDirty(): bool {
        return $this->dirty;
    }

    /**
     * @return array<string>
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
                $ownerId        = $row->{$owner};
                $data[$ownerId] = (int) $row->count;
            }

            // Save
            $this->assetsCount[$owner] = $data;
        }

        return $this->assetsCount[$owner];
    }

    /**
     * @return array<string, array<string, array<string, int>>>
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

    public function modelSaved(Model $model): void {
        $this->dirty = true;
    }

    public function modelDeleted(Model $model): void {
        $this->dirty = true;
    }
}
