<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated\GroupBy\Operators;

use App\GraphQL\Directives\Directives\Aggregated\GroupBy\Directive;
use App\GraphQL\Directives\Directives\Aggregated\GroupBy\Types\Direction;
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
        return 'Group by property.';
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
        $column    = Cast::toString($property->getName());
        $grammar   = $builder->getGrammar();
        $direction = $this->getKeyDirection($argument);
        $builder   = $builder
            ->toBase()
            ->select([
                DB::raw("{$this->getKeyExpression($builder, $column)} as {$grammar->wrap('key')}"),
                DB::raw("count(*) as {$grammar->wrap('count')}"),
            ])
            ->groupBy('key');

        if ($direction) {
            $builder = $builder->orderBy('key', $direction);
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

    protected function getKeyDirection(Argument $argument): ?string {
        return Cast::toString($argument->value);
    }
}
