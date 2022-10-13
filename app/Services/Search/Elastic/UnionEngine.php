<?php declare(strict_types = 1);

namespace App\Services\Search\Elastic;

use App\Services\Search\Builders\UnionBuilder;
use Elastic\ScoutDriverPlus\Decorators\Hit;
use Elastic\ScoutDriverPlus\Decorators\SearchResult;
use Generator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use InvalidArgumentException;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use LogicException;

use function assert;
use function sprintf;

class UnionEngine extends Engine {
    public function __construct(
        protected SearchRequestFactory $factory,
    ) {
        // empty
    }

    // <editor-fold desc="Search">
    // =========================================================================
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
     *
     * @return Collection<array-key, string>
     */
    public function mapIds($results): Collection {
        // TODO: Probably we need to add Model class?
        assert($results instanceof SearchResult);

        return (new Collection($results->hits()))
            ->map(static function (mixed $match): string {
                assert($match instanceof Hit);

                return $match->document()->id();
            });
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
     *
     * @return EloquentCollection<array-key, Model>
     */
    public function map(Builder $builder, $results, $model): EloquentCollection {
        assert($results instanceof SearchResult);

        return $results->models();
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
     *
     * @return LazyCollection<array-key, Model>
     */
    public function lazyMap(Builder $builder, $results, $model): LazyCollection {
        assert($results instanceof SearchResult);

        return new LazyCollection(static function () use ($results): Generator {
            foreach ($results as $query) {
                yield $query->model();
            }
        });
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
     */
    public function getTotalCount($results): int {
        assert($results instanceof SearchResult);

        return (int) $results->total();
    }

    /**
     * {@inheritDoc}
     */
    public function search(Builder $builder) {
        return $this->execute($builder);
    }

    /**
     * {@inheritDoc}
     */
    public function paginate(Builder $builder, $perPage, $page) {
        return $this->execute($builder, [
            'perPage' => $perPage,
            'page'    => $page,
        ]);
    }

    /**
     * @param array<mixed> $options
     */
    protected function execute(Builder $builder, array $options = []): SearchResult {
        if (!($builder instanceof UnionBuilder)) {
            throw new InvalidArgumentException(sprintf(
                'The `%s` must be instance of `%s`, `%s` given.',
                '$builder',
                UnionBuilder::class,
                $builder::class,
            ));
        }

        return $this->factory
            ->makeFromUnionBuilder($builder, $options)
            ->execute();
    }
    // </editor-fold>

    // <editor-fold desc="Not supported">
    // =========================================================================
    /**
     * @inheritdoc
     */
    public function update($models) {
        throw new LogicException('Not supported.');
    }

    /**
     * @inheritdoc
     */
    public function delete($models) {
        throw new LogicException('Not supported.');
    }

    /**
     * @inheritdoc
     */
    public function flush($model) {
        throw new LogicException('Not supported.');
    }

    /**
     * @inheritdoc
     */
    public function createIndex($name, array $options = []) {
        throw new LogicException('Not supported.');
    }

    /**
     * @inheritdoc
     */
    public function deleteIndex($name) {
        throw new LogicException('Not supported.');
    }
    // </editor-fold>
}
