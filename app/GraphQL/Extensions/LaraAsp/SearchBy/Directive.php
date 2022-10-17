<?php declare(strict_types = 1);

namespace App\GraphQL\Extensions\LaraAsp\SearchBy;

use Illuminate\Database\Eloquent\Builder;
use LastDragon_ru\LaraASP\GraphQL\Builder\Property;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Definitions\SearchByDirective;
use Nuwave\Lighthouse\Execution\Arguments\ArgumentSet;

class Directive extends SearchByDirective {
    public function handle(object $builder, Property $property, ArgumentSet $conditions): object {
        // For some relations like `HasManyThrough` we need to add table name to
        // avoid SQLSTATE[23000]: Integrity constraint violation: 1052 Column
        // `id`' in where clause is ambiguous
        if ($builder instanceof Builder && $property->getPath() === []) {
            $property = $property->getChild($builder->getModel()->getTable());
        }

        return parent::handle($builder, $property, $conditions);
    }
}
