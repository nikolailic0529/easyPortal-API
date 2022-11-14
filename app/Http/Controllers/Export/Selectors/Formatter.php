<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

use App\Http\Controllers\Export\Selector;
use App\Services\I18n\Formatter as I18nFormatter;

abstract class Formatter extends Modifier {
    /**
     * @param array<Selector> $arguments
     * @param int<0, max>     $index
     */
    public function __construct(
        protected I18nFormatter $formatter,
        array $arguments,
        int $index,
    ) {
        parent::__construct($arguments, $index);
    }

    public static function getArgumentsMinCount(): ?int {
        return 1;
    }

    public static function getArgumentsMaxCount(): ?int {
        return 1;
    }
}
