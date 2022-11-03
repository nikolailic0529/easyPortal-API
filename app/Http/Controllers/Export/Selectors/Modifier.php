<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

use App\Http\Controllers\Export\Exceptions\SelectorToFewArguments;
use App\Http\Controllers\Export\Exceptions\SelectorToManyArguments;
use App\Http\Controllers\Export\Selector;

use function count;
use function reset;

abstract class Modifier implements Selector {
    /**
     * @param array<Selector> $arguments
     * @param int<0, max>     $index
     */
    public function __construct(
        protected array $arguments,
        protected int $index,
    ) {
        $min   = static::getArgumentsMinCount();
        $max   = static::getArgumentsMaxCount();
        $name  = static::getName();
        $count = count($this->arguments);

        if ($min !== null && $count < $min) {
            throw new SelectorToFewArguments($name, $min, $count);
        }

        if ($max !== null && $count > $max) {
            throw new SelectorToManyArguments($name, $max, $count);
        }
    }

    abstract public static function getName(): string;

    abstract public static function getArgumentsMinCount(): ?int;

    abstract public static function getArgumentsMaxCount(): ?int;

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
