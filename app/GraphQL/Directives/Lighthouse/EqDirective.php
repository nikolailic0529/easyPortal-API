<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Lighthouse;

use Nuwave\Lighthouse\Schema\Directives\EqDirective as LighthouseEqDirective;

class EqDirective extends LighthouseEqDirective {
    /**
     * @inheritDoc
     */
    public function handleBuilder($builder, $value): object {
        return $builder->where(
            $builder->qualifyColumn($this->directiveArgValue('key') ?? $this->nodeName()),
            $value,
        );
    }
}
