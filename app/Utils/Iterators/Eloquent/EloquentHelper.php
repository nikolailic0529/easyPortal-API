<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use LastDragon_ru\LaraASP\Eloquent\Iterators\IteratorImpl;

use function max;
use function min;

/**
 * @extends IteratorImpl<Model>
 */
class EloquentHelper extends IteratorImpl {
    public function __construct() {
        parent::__construct((new class() extends Model {
            // empty
        })::query());
    }

    protected function getChunk(Builder $builder, int $chunk): Collection {
        return new Collection();
    }

    /**
     * @param IteratorImpl<Model> $iterator
     *
     * @return int<0, max>
     */
    public function getCount(IteratorImpl $iterator): int {
        $limit = $iterator->getLimit();
        $count = $iterator->builder->count();
        $count = $limit !== null ? min($limit, $count) : $count;
        $count = max(0, $count);

        return $count;
    }
}
