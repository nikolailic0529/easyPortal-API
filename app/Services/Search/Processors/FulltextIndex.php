<?php declare(strict_types = 1);

namespace App\Services\Search\Processors;

use SplPriorityQueue;

/**
 * @extends SplPriorityQueue<int, string>
 */
class FulltextIndex extends SplPriorityQueue {
    /**
     * @noinspection PhpMissingParentConstructorInspection
     * @phpstan-ignore-next-line https://github.com/php/php-src/issues/8457
     */
    public function __construct(
        protected string $name,
        protected string $sql,
    ) {
        // empty
    }

    public function getName(): string {
        return $this->name;
    }

    public function getSql(): string {
        return $this->sql;
    }
}
