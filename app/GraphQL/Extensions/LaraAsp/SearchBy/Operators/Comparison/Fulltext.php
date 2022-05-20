<?php declare(strict_types = 1);

namespace App\GraphQL\Extensions\LaraAsp\SearchBy\Operators\Comparison;

use App\GraphQL\Extensions\LaraAsp\SearchBy\Metadata;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\Contains;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\EndsWith;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\StartsWith;

use function mb_strlen;

/**
 * @mixin Contains
 * @mixin StartsWith
 * @mixin EndsWith
 */
trait Fulltext {
    public function __construct(
        protected Repository $config,
        protected Metadata $metadata,
    ) {
        parent::__construct();
    }

    /**
     * @param EloquentBuilder<Model>|QueryBuilder $builder
     *
     * @return EloquentBuilder<Model>|QueryBuilder
     */
    public function apply(
        EloquentBuilder|QueryBuilder $builder,
        string $property,
        mixed $value,
    ): EloquentBuilder|QueryBuilder {
        $min    = $this->config->get('ep.search.fulltext.ngram_token_size') ?? 2;
        $length = mb_strlen((string) $value);

        if ($this->match($builder, $property) && $length >= $min) {
            $builder = $builder->where(function (EloquentBuilder|QueryBuilder $builder) use ($property, $value): void {
                parent::apply($builder, $property, $value)->whereMatchAgainst($property, $value);
            });
        } else {
            $builder = parent::apply($builder, $property, $value);
        }

        return $builder;
    }

    /**
     * @param EloquentBuilder<Model>|QueryBuilder $builder
     */
    protected function match(EloquentBuilder|QueryBuilder $builder, string $property): bool {
        return $this->metadata->isFulltextIndexExists($builder, $property);
    }
}
