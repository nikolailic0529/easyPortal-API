<?php declare(strict_types = 1);

namespace App\GraphQL\Extensions\LaraAsp\SearchBy\Operators\Comparison;

use App\GraphQL\Extensions\LaraAsp\SearchBy\Metadata;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use LastDragon_ru\LaraASP\Core\Utils\Cast;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Handler;
use LastDragon_ru\LaraASP\GraphQL\Builder\Exceptions\OperatorUnsupportedBuilder;
use LastDragon_ru\LaraASP\GraphQL\Builder\Property;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\Contains;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\EndsWith;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\StartsWith;
use Nuwave\Lighthouse\Execution\Arguments\Argument;

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

    public function call(Handler $handler, object $builder, Property $property, Argument $argument): object {
        if (!($builder instanceof EloquentBuilder || $builder instanceof QueryBuilder)) {
            throw new OperatorUnsupportedBuilder($this, $builder);
        }

        $min    = $this->config->get('ep.search.fulltext.ngram_token_size') ?? 2;
        $name   = $builder->getGrammar()->wrap((string) $property->getParent());
        $value  = (string) Cast::toStringable($argument->toPlain());
        $length = mb_strlen($value);

        if ($this->match($builder, $name) && $length >= $min) {
            $builder = $builder->where(
                function (
                    EloquentBuilder|QueryBuilder $builder,
                ) use (
                    $handler,
                    $property,
                    $argument,
                    $name,
                    $value,
                ): void {
                    parent::call($handler, $builder, $property, $argument)
                        ->whereMatchAgainst($name, $value);
                },
            );
        } else {
            $builder = parent::call($handler, $builder, $property, $argument);
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
