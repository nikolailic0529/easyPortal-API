<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

use App\Services\Queue\Queue;
use App\Services\Queue\ServiceResolver;

class ResetApplicationService {
    public function __construct(
        protected ServiceResolver $resolver,
        protected Queue $queue,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array{setting: array<mixed>}
     */
    public function __invoke(mixed $root, array $args): array {
        $service = $this->resolver->get($args['input']['name'] ?? '');
        $result  = $this->queue->resetProgress($service);

        return [
            'result' => $result,
        ];
    }
}
