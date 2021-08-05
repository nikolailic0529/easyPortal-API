<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Services\Search\Eloquent\UnionModel;
use Illuminate\Contracts\Container\Container;

class UnionBuilder extends Builder {
    /**
     * @var array<
     *      class-string<\App\Models\Model&\App\Services\Search\Eloquent\Searchable>,
     *      array{scopes:array<\App\Services\Search\Scope>,boost:float|null}
     *      > $models
     */
    protected array $models = [];

    public function __construct(
        Container $container,
        UnionModel $model,
        string $query,
        callable $callback = null,
        bool $softDelete = false,
    ) {
        parent::__construct($container, $model, $query, $callback, $softDelete);

        $this->query = $query;
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable> $model
     * @param array<\App\Services\Search\Scope>                                                          $scopes
     */
    public function addModel(string $model, array $scopes, float $boost = null): static {
        $this->models[$model] = [
            'scopes' => $scopes,
            'boost'  => $boost,
        ];

        // Return
        return $this;
    }

    /**
     * @return array<
     *      class-string<\App\Models\Model&\App\Services\Search\Eloquent\Searchable>,
     *      array{scopes:array<\App\Services\Search\Scope>,boost:float|null}
     *      > $models
     */
    public function getModels(): array {
        return $this->models;
    }
}
