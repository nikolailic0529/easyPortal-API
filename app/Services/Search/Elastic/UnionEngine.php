<?php declare(strict_types = 1);

namespace App\Services\Search\Elastic;

use App\Services\Search\UnionBuilder;
use ElasticScoutDriverPlus\QueryMatch;
use ElasticScoutDriverPlus\SearchResult;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use InvalidArgumentException;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use LogicException;

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
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     *
     * @param \ElasticScoutDriverPlus\SearchResult $results
     */
    public function mapIds($results): Collection {
        // TODO: Probably we need to add Model class?
        return (new Collection($results->matches()))
            ->map(static function (QueryMatch $match): string {
                return $match->document()->getId();
            });
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     *
     * @param \ElasticScoutDriverPlus\SearchResult $results
     * @param \Illuminate\Database\Eloquent\Model  $model
     */
    public function map(Builder $builder, $results, $model): Collection {
        return $results->models();
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     *
     * @param \ElasticScoutDriverPlus\SearchResult $results
     * @param \Illuminate\Database\Eloquent\Model  $model
     */
    public function lazyMap(Builder $builder, $results, $model): LazyCollection {
        return new LazyCollection(function () use ($builder, $results, $model): Collection {
            return $this->map($builder, $results, $model);
        });
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     *
     * @param \ElasticScoutDriverPlus\SearchResult $results
     */
    public function getTotalCount($results): ?int {
        return $results->total();
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
            'perPage' => (int) $perPage,
            'page'    => (int) $page,
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
     * {@inheritDoc}
     */
    public function update($models) {
        throw new LogicException('Not supported.');
    }

    /**
     * {@inheritDoc}
     */
    public function delete($models) {
        throw new LogicException('Not supported.');
    }

    /**
     * {@inheritDoc}
     */
    public function flush($model) {
        throw new LogicException('Not supported.');
    }

    /**
     * {@inheritDoc}
     */
    public function createIndex($name, array $options = []) {
        throw new LogicException('Not supported.');
    }

    /**
     * {@inheritDoc}
     */
    public function deleteIndex($name) {
        throw new LogicException('Not supported.');
    }
    // </editor-fold>
}