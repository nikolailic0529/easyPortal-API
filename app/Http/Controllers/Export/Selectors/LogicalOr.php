<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

class LogicalOr extends Modifier {
    public static function getName(): string {
        return 'or';
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
        $row[$this->index] = null;

        foreach ($this->arguments as $argument) {
            $value = $this->getArgumentValue($argument, $item);

            if ($value) {
                $row[$this->index] = $value;
                break;
            }
        }
    }
}
