<?php declare(strict_types = 1);

namespace App\GraphQL\Extensions\Lighthouse\Directives;

use Nuwave\Lighthouse\Execution\Arguments\ArgumentSet;
use Nuwave\Lighthouse\Validation\ValidatorDirective as LighthouseValidatorDirective;

/**
 * @see https://github.com/nuwave/lighthouse/issues/2041
 */
class ValidatorDirective extends LighthouseValidatorDirective {
    /**
     * @inheritDoc
     */
    public function setArgumentValue($argument): self {
        $result = parent::setArgumentValue($argument);

        if ($argument instanceof ArgumentSet) {
            $this->validator()->setArgs($argument);
        }

        return $result;
    }
}
