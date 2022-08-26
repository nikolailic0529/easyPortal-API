<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated\GroupBy\Operators;

use App\GraphQL\Directives\Directives\Aggregated\GroupBy\Directive;
use App\GraphQL\Directives\Directives\Aggregated\GroupBy\Types\Direction;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LastDragon_ru\LaraASP\Core\Utils\Cast;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Handler;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\TypeProvider;
use LastDragon_ru\LaraASP\GraphQL\Builder\Directives\OperatorDirective;
use LastDragon_ru\LaraASP\GraphQL\Builder\Exceptions\OperatorUnsupportedBuilder;
use LastDragon_ru\LaraASP\GraphQL\Builder\Property;
use Nuwave\Lighthouse\Execution\Arguments\Argument;

use function array_slice;
use function end;
use function implode;

abstract class BaseOperator extends OperatorDirective {
    public static function getDirectiveName(): string {
        return implode('', [
            '@',
            Str::camel(Directive::NAME),
            'Operator',
            Str::studly(static::getName()),
        ]);
    }

    public function getFieldType(TypeProvider $provider, string $type): ?string {
        return $provider->getType(Direction::class);
    }

    public function getFieldDescription(): string {
        return 'Property clause.';
    }

    public function isBuilderSupported(object $builder): bool {
        return $builder instanceof Builder;
    }

    public function call(Handler $handler, object $builder, Property $property, Argument $argument): object {
        // Supported?
        if (!($builder instanceof Builder)) {
            throw new OperatorUnsupportedBuilder($this, $builder);
        }

        // Process
        // Column
        $path      = $property->getPath();
        $column    = Cast::toString(end($path));
        $grammar   = $builder->getGrammar();
        $relation  = array_slice($path, 0, -1);
        $direction = Cast::toString($argument->value);

        if ($relation) {
            // -> join relation
            // -> create union with `null`

            throw new Exception('not implemented');
        } else {
            $builder = $builder
                ->toBase()
                ->select([
                    DB::raw("{$this->getKeyExpression($builder, $column)} as {$grammar->wrap('key')}"),
                    DB::raw("count(*) as {$grammar->wrap('count')}"),
                ])
                ->groupBy('key')
                ->orderBy('key', $direction);
        }

        /** @phpstan-ignore-next-line builder is different but it is ok in this case */
        return $builder;
    }

    /**
     * Should return wrapped column/expression string that will be used as a key.
     *
     * @param Builder<Model> $builder
     */
    abstract protected function getKeyExpression(Builder $builder, string $column): string;
}
