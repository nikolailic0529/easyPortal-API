<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application\Maintenance;

use App\Services\Maintenance\Maintenance;

class Stop {
    public function __construct(
        protected Maintenance $maintenance,
    ) {
        // empty
    }

    public function __invoke(): mixed {
        return [
            'result' => $this->maintenance->stop(),
        ];
    }
}
