<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

use function filter_var;
use function reset;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_FLOAT;

class Integer extends Formatter {
    public static function getName(): string {
        return 'int';
    }

    /**
     * @inheritdoc
     */
    public function fill(array $item, array &$row): void {
        $arguments         = $this->getArguments($item);
        $argument          = reset($arguments);
        $row[$this->index] = $this->formatter->integer(
            filter_var($argument, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE),
        );
    }
}
