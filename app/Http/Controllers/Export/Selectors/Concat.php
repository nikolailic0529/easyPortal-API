<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

use function array_filter;
use function implode;

class Concat extends Modifier {
    public static function getName(): string {
        return 'concat';
    }

    public static function getArgumentsMinCount(): ?int {
        return 2;
    }

    public static function getArgumentsMaxCount(): ?int {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function fill(array $item, array &$row): void {
        $row[$this->index] = implode(' ', array_filter($this->getArguments($item)));
    }
}
