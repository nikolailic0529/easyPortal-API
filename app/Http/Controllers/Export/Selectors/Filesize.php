<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

use function filter_var;
use function reset;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_INT;

class Filesize extends Formatter {
    public static function getName(): string {
        return 'filesize';
    }

    /**
     * @inheritdoc
     */
    public function fill(array $item, array &$row): void {
        $arguments         = $this->getArguments($item);
        $argument          = reset($arguments);
        $row[$this->index] = $this->formatter->filesize(
            filter_var($argument, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE),
        );
    }
}
