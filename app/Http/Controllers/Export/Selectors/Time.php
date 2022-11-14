<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

use Illuminate\Support\Facades\Date as DateFactory;
use IntlDateFormatter;

use function reset;

class Time extends Formatter {
    public static function getName(): string {
        return 'time';
    }

    /**
     * @inheritdoc
     */
    public function fill(array $item, array &$row): void {
        $arguments         = $this->getArguments($item);
        $argument          = reset($arguments);
        $row[$this->index] = $this->formatter->time(
            DateFactory::make($argument),
            IntlDateFormatter::MEDIUM,
        );
    }
}
