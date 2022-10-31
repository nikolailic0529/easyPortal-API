<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

use App\Http\Controllers\Export\Selector;

use function reset;

abstract class Modifier implements Selector {
    /**
     * @param non-empty-array<Selector> $arguments
     */
    public function __construct(
        protected array $arguments,
        protected int $index,
    ) {
        // empty
    }

    /**
     * @param array<scalar|null|array<scalar|null>> $item
     *
     * @return array<string|float|int|bool|null>
     */
    protected function getArguments(array $item): array {
        $arguments = [];

        foreach ($this->arguments as $argument) {
            $arguments[] = $this->getArgumentValue($argument, $item);
        }

        return $arguments;
    }

    /**
     * @param array<scalar|null|array<scalar|null>> $item
     */
    protected function getArgumentValue(Selector $argument, array $item): string|float|int|bool|null {
        $row = [];

        $argument->fill($item, $row);

        return reset($row);
    }
}
