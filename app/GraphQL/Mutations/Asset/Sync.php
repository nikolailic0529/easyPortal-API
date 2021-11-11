<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Asset;

use App\Services\DataLoader\Jobs\AssetSync;
use Illuminate\Contracts\Container\Container;

class Sync {
    public function __construct(
        protected Container $container,
    ) {
        // empty
    }

    /**
     * @param array{input: array{id: array<string>}} $args
     *
     * @return array{result: bool}
     */
    public function __invoke(mixed $root, array $args): array {
        foreach ($args['input']['id'] as $id) {
            $this->container
                ->make(AssetSync::class)
                ->init(
                    id           : $id,
                    warrantyCheck: true,
                    documents    : true,
                )
                ->run();
        }

        return [
            'result' => true,
        ];
    }
}
