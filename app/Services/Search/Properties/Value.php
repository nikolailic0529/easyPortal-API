<?php declare(strict_types = 1);

namespace App\Services\Search\Properties;

/**
 * @template T of \App\Services\Search\Properties\Property|\App\Services\Search\Properties\Relation
 */
abstract class Value extends Property {
    public function __construct(
        string $name,
        protected bool $searchable = false,
    ) {
        parent::__construct($name);
    }

    public function isSearchable(): bool {
        return $this->searchable;
    }

    abstract public function getType(): string;

    public function hasKeyword(): bool {
        return false;
    }

    /**
     * @return array<string,array{type:string}>|null
     */
    public function getFields(): ?array {
        $fields = [];

        if ($this->hasKeyword()) {
            $fields['keyword'] = ['type' => 'keyword'];
        }

        return $fields ?: null;
    }
}
