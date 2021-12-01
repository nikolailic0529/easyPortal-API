<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application\Maintenance;

use App\Services\Maintenance\Maintenance;

class Schedule {
    public function __construct(
        protected Maintenance $maintenance,
    ) {
        // empty
    }

    /**
     * @param array{input:array{start: \DateTimeInterface, end: \DateTimeInterface, message: string}} $args
     */
    public function __invoke(mixed $root, array $args): mixed {
        return [
            'result' => $this->maintenance->schedule(
                $args['input']['start'],
                $args['input']['end'],
                $args['input']['message'],
            ),
        ];
    }
}
