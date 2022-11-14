<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

use Illuminate\Support\Facades\Date as DateFactory;
use IntlDateFormatter;

use function reset;

class Date extends Formatter {
    public static function getName(): string {
        return 'date';
    }

    /**
     * @inheritdoc
     */
    public function fill(array $item, array &$row): void {
        $arguments         = $this->getArguments($item);
        $argument          = reset($arguments);
        $row[$this->index] = $this->formatter->date(
            DateFactory::make($argument),
            IntlDateFormatter::MEDIUM,
            'UTC',
        );
    }
}
