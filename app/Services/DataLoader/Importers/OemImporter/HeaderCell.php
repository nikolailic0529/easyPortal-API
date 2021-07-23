<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers\OemImporter;

class HeaderCell {
    public function __construct(
        protected string $index,
        protected string $key,
        protected ?CellType $type,
        protected ?string $locale,
    ) {
        // empty
    }

    public function getIndex(): string {
        return $this->index;
    }

    public function getKey(): string {
        return $this->key;
    }

    public function getType(): ?CellType {
        return $this->type;
    }

    public function getLocale(): ?string {
        return $this->locale;
    }
}
