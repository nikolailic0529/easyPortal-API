<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

use App\Services\Queue\Utils\ServiceResolver;

class DispatchApplicationService {
    public function __construct(
        protected ServiceResolver $resolver,
    ) {
        // empty
    }

    /**
     * @param array{input:array{name: string, immediately: bool}} $args
     *
     * @return array{result: bool}
     */
    public function __invoke(mixed $root, array $args): array {
        $result      = false;
        $service     = $this->resolver->get($args['input']['name']);
        $immediately = $args['input']['immediately'];

        if ($immediately) {
            $result = (bool) $service->run();
        } else {
            $result = (bool) $service->dispatch();
        }

        return [
            'result' => $result,
        ];
    }
}
