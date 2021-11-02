<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export;

class QueryExported {
    /**
     * @param array<string>|null $headers
     */
    public function __construct(
        protected string $type,
        protected string $root,
        protected ?array $headers,
    ) {
        // empty
    }

    public function getType(): string {
        return $this->type;
    }

    public function getRoot(): string {
        return $this->root;
    }

    /**
     * @return array<string>|null
     */
    public function getHeaders(): ?array {
        return $this->headers;
    }
}
