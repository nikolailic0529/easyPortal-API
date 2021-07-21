<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers\OemImporter;

class HeaderCell {
    public function __construct(
        protected string $key,
        protected ?CellType $type,
    ) {
        // empty
    }

    public function getKey(): string {
        return $this->key;
    }

    public function getType(): ?CellType {
        return $this->type;
    }
}
