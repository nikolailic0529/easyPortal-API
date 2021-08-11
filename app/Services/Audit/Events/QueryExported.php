<?php declare(strict_types = 1);

namespace App\Services\Audit\Events;

use Illuminate\Queue\SerializesModels;

class QueryExported {
    use SerializesModels;

    public function __construct(
        protected int $count,
        protected string $type,
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

    /**
     * @return array<string>
     */
    public function getColumns(): ?array {
        return $this->columns;
    }
}
