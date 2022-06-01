<?php declare(strict_types = 1);

namespace App\Services\Search\Builders;

use App\Services\Search\Contracts\Scope;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Eloquent\UnionModel;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Builder<Model&Searchable>
 */
class UnionBuilder extends Builder {
    /**
     * @var array<class-string<Model&Searchable>,array{scopes:array<Scope<Model&Searchable>>,boost:float|null}> $models
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
    }

    /**
     * @param class-string<Model&Searchable> $model
     * @param array<Scope<Model&Searchable>> $scopes
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
     * @return array<class-string<Model&Searchable>,array{scopes:array<Scope<Model&Searchable>>,boost:float|null}>
     */
    public function getModels(): array {
        return $this->models;
    }
}
