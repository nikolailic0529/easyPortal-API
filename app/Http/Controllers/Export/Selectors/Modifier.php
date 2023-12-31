<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

use App\Http\Controllers\Export\Exceptions\SelectorFunctionToFewArguments;
use App\Http\Controllers\Export\Exceptions\SelectorFunctionToManyArguments;
use App\Http\Controllers\Export\Selector;

use function array_merge;
use function array_unique;
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
            throw new SelectorFunctionToFewArguments($name, $min, $count);
        }

        if ($max !== null && $count > $max) {
            throw new SelectorFunctionToManyArguments($name, $max, $count);
        }
    }

    abstract public static function getName(): string;

    abstract public static function getArgumentsMinCount(): ?int;

    abstract public static function getArgumentsMaxCount(): ?int;

    /**
     * @inheritdoc
     */
    public function getSelectors(): array {
        $selectors = [];

        foreach ($this->arguments as $argument) {
            $selectors = array_merge($selectors, $argument->getSelectors());
        }

        return array_unique($selectors);
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
