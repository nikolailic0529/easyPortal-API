<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

use App\Services\Queue\Queue;
use App\Services\Queue\Utils\ServiceResolver;

class ResetApplicationService {
    public function __construct(
        protected ServiceResolver $resolver,
        protected Queue $queue,
    ) {
        // empty
    }

    /**
     * @param array{input:array{name: string}} $args
     *
     * @return array{result: bool}
     */
    public function __invoke(mixed $root, array $args): array {
        $service = $this->resolver->get($args['input']['name']);
        $result  = $this->queue->resetProgress($service);

        return [
            'result' => $result,
        ];
    }
}
