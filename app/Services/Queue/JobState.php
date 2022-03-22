<?php declare(strict_types = 1);

namespace App\Services\Queue;

use DateTimeInterface;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.NotCamelCaps
//      https://github.com/squizlabs/PHP_CodeSniffer/issues/3564

class JobState {
    public function __construct(
        public string $name,
        public string $id,
        public bool $running,
        public bool $stopped,
        public ?DateTimeInterface $pending_at,
        public ?DateTimeInterface $running_at,
    ) {
        // empty
    }
}
