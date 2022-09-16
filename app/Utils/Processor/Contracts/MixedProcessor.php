<?php declare(strict_types = 1);

namespace App\Utils\Processor\Contracts;

use App\Utils\Processor\State;

/**
 * Pseudo-type for PHPStan.
 *
 * @see https://github.com/phpstan/phpstan/issues/3960
 *
 * @internal
 *
 * @extends Processor<mixed, mixed, State>
 */
interface MixedProcessor extends Processor {
    // empty
}
