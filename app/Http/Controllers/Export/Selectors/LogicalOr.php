<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

class LogicalOr extends Modifier {
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
