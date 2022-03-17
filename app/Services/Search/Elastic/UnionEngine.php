<?php declare(strict_types = 1);

namespace App\Services\Search\Elastic;

use App\Services\Search\Builders\UnionBuilder;
use ElasticScoutDriverPlus\Decorators\Hit;
use ElasticScoutDriverPlus\Decorators\SearchResult;
use Generator;
use Illuminate\Database\Eloquent\Model;
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
     * @param SearchResult $results
     */
    public function mapIds($results): Collection {
        // TODO: Probably we need to add Model class?
        return (new Collection($results->matches()))
            ->map(static function (Hit $match): string {
                return $match->document()->id();
            });
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     *
     * @param SearchResult $results
     * @param Model        $model
     */
    public function map(Builder $builder, $results, $model): Collection {
        return $results->models();
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     *
     * @param SearchResult $results
     * @param Model        $model
     */
    public function lazyMap(Builder $builder, $results, $model): LazyCollection {
        return new LazyCollection(static function () use ($results): Generator {
            foreach ($results as $query) {
                /** @var Hit $query */
                yield $query->model();
            }
        });
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     *
     * @param SearchResult $results
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
