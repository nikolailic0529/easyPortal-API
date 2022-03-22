<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Data;

use DateTimeZone;

class Timezones {
    /**
     * @param array<string, mixed> $args
     *
     * @return array<string>
     */
    public function __invoke(mixed $root, array $args): array {
        return DateTimeZone::listIdentifiers();
    }
}
