<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\SearchBy\Operators\Comparison;

use App\GraphQL\Directives\SearchBy\Metadata;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Contracts\ComparisonOperator;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\BaseOperator;

use function strtr;

abstract class Like extends BaseOperator implements ComparisonOperator {
    public function __construct(
        protected Metadata $metadata,
    ) {
        parent::__construct();
    }

    public function apply(
        EloquentBuilder|QueryBuilder $builder,
        string $property,
        mixed $value,
    ): EloquentBuilder|QueryBuilder {
        return $builder->where(
            function (EloquentBuilder|QueryBuilder $builder) use ($property, $value): void {
                $builder
                    ->where($property, 'like', $this->value($this->escape($value)))
                    ->when(
                        $this->match($builder, $property),
                        static function (EloquentBuilder|QueryBuilder $builder) use ($property, $value): void {
                            $builder->whereMatchAgainst($property, $value);
                        },
                    );
            },
        );
    }

    abstract protected function value(string $string): string;

    protected function match(EloquentBuilder|QueryBuilder $builder, string $property): bool {
        return $this->metadata->isFulltextIndexExists($builder, $property);
    }

    protected function escape(string $string): string {
        // https://dev.mysql.com/doc/refman/8.0/en/string-comparison-functions.html#operator_like
        $escape  = '\\';
        $escaped = strtr($string, [
            "\n"    => "\n",
            "\r"    => "\n",
            '%'     => "{$escape}{$escape}%",
            '_'     => "{$escape}{$escape}_",
            $escape => "{$escape}{$escape}{$escape}{$escape}",
        ]);

        return $escaped;
    }
}
