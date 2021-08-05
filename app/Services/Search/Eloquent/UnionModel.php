<?php declare(strict_types = 1);

namespace App\Services\Search\Eloquent;

use App\Services\Search\Elastic\UnionEngine;
use App\Services\Search\UnionBuilder;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Engines\Engine;
use Laravel\Scout\Searchable;

use function app;
use function config;

/**
 * Fake model for Lighthouse to create {@link \App\Services\Search\UnionBuilder}.
 */
class UnionModel extends Model {
    use Searchable;

    public static function search(string $query = '', Closure $callback = null): UnionBuilder {
        return app()->make(UnionBuilder::class, [
            'model'      => new static(),
            'query'      => $query,
            'callback'   => $callback,
            'softDelete' => static::usesSoftDelete() && config('scout.soft_delete', false),
        ]);
    }

    public function searchableUsing(): Engine {
        return app()->make(UnionEngine::class);
    }
}
