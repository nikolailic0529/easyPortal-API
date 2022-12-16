<?php declare(strict_types = 1);

namespace App\Services\Search\Eloquent;

use App\Services\Search\Builders\Builder;
use App\Services\Search\Builders\UnionBuilder;
use App\Services\Search\Elastic\UnionEngine;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Engines\Engine;

use function config;

/**
 * Fake model for Lighthouse to create {@link \App\Services\Search\Builders\UnionBuilder}.
 */
class UnionModel extends Model implements Searchable {
    use SearchableImpl;

    /**
     * @param array<string, mixed> $attributes
     */
    final public function __construct(array $attributes = []) {
        parent::__construct($attributes);
    }

    public static function search(string $query = ''): Builder {
        return Container::getInstance()->make(UnionBuilder::class, [
            'model'      => new static(),
            'query'      => $query,
            'callback'   => null,
            'softDelete' => static::usesSoftDelete() && config('scout.soft_delete', false),
        ]);
    }

    public function searchableUsing(): Engine {
        return Container::getInstance()->make(UnionEngine::class);
    }

    /**
     * @inheritDoc
     */
    public static function getSearchProperties(): array {
        return [];
    }
}
