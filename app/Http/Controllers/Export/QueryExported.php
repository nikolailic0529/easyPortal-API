<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export;

use Illuminate\Queue\SerializesModels;

class QueryExported {
    use SerializesModels;

    public function __construct(
        protected int $count,
        protected string $type,
        protected string $query,
        protected ?array $columns,
    ) {
        // empty
    }

    public function getCount(): int {
        return $this->count;
    }

    public function getType(): string {
        return $this->type;
    }

    public function getQuery(): string {
        return $this->query;
    }

    /**
     * @return array<string>
     */
    public function getColumns(): ?array {
        return $this->columns;
    }
}
