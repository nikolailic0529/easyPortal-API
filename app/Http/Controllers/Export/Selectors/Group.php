<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

use App\Http\Controllers\Export\Selector;

use function is_array;

class Group implements Selector {
    /**
     * @param array<Selector> $selectors
     */
    public function __construct(
        protected string $field,
        protected array $selectors = [],
    ) {
        // empty
    }

    public function add(Selector $selector): static {
        $this->selectors[] = $selector;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function fill(array $item, array &$row): void {
        $value = $item[$this->field] ?? null;

        if (is_array($value)) {
            foreach ($this->selectors as $selector) {
                $selector->fill($value, $row);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getSelectors(): array {
        $selectors = [];

        foreach ($this->selectors as $nested) {
            foreach ($nested->getSelectors() as $selector) {
                $selectors[] = "{$this->field}.{$selector}";
            }
        }

        return $selectors;
    }
}
