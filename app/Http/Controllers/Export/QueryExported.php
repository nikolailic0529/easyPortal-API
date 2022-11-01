<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export;

/**
 * @phpstan-import-type Query from ExportRequest
 */
class QueryExported {
    /**
     * @param Query $query
     */
    public function __construct(
        protected string $type,
        protected array $query,
    ) {
        // empty
    }

    public function getType(): string {
        return $this->type;
    }

    /**
     * @return Query
     */
    public function getQuery(): array {
        return $this->query;
    }
}
