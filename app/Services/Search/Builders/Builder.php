<?php declare(strict_types = 1);

namespace App\Services\Search\Builders;

use App\Services\Search\Configuration;
use App\Services\Search\Scope;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Laravel\Scout\Builder as ScoutBuilder;

use function is_a;
use function is_string;
use function sprintf;

class Builder extends ScoutBuilder {
    /**
     * The "where not" constraints added to the query.
     *
     * @var array<string,mixed>
     */
    public array $whereNots = [];

    /**
     * The "where not in" constraints added to the query.
     *
     * @var array<string,array<mixed>>
     */
    public array $whereNotIns = [];

    /**
     * The "offset" constraint added to the query.
     */
    public ?int $offset = null;

    public function __construct(
        protected Container $container,
        Model $model,
        string $query,
        callable $callback = null,
        bool $softDelete = false,
    ) {
        // Parent
        parent::__construct($model, $query, $callback, $softDelete);

        // Global scopes
        $scopes = $model->getGlobalScopes();

        foreach ($scopes as $scope) {
            if ($scope instanceof Scope || (is_string($scope) && is_a($scope, Scope::class, true))) {
                $this->applyScope($scope);
            }
        }
    }

    /**
     * @param \App\Services\Search\Scope|class-string<\App\Services\Search\Scope> $scope
     */
    public function applyScope(Scope|string $scope): static {
        if (is_string($scope)) {
            $scope = $this->container->make($scope);
        }

        if (!($scope instanceof Scope)) {
            throw new InvalidArgumentException(sprintf(
                'The `%s` must be instance of `%s`, `%s` given.',
                '$scope',
                Scope::class,
                $scope::class,
            ));
        }

        $scope->applyForSearch($this, $this->model);

        return $this;
    }

    public function whereNot(string $field, mixed $value): static {
        $this->whereNots[$field] = $value;

        return $this;
    }

    /**
     * @param array<mixed> $values
     */
    public function whereNotIn(string $field, array $values): static {
        $this->whereNotIns[$field] = $values;

        return $this;
    }

    public function whereMetadata(string $field, mixed $value): static {
        return $this->where(Configuration::getMetadataName($field), $value);
    }

    /**
     * @param array<mixed> $values
     */
    public function whereMetadataIn(string $field, array $values): static {
        return $this->whereIn(Configuration::getMetadataName($field), $values);
    }

    /**
     * @param array<mixed> $values
     */
    public function whereMetadataNotIn(string $field, array $values): static {
        return $this->whereNotIn(Configuration::getMetadataName($field), $values);
    }

    public function offset(?int $offset): static {
        $this->offset = $offset;

        return $this;
    }

    public function count(): int {
        return $this->getTotalCount((clone $this)->take(1)->raw());
    }
}
