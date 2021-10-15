<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Asset;

use App\Services\DataLoader\Jobs\AssetUpdate;
use Illuminate\Contracts\Container\Container;

class Sync {
    public function __construct(
        protected Container $container,
    ) {
        // empty
    }

    /**
     * @param array{input: array<array{id: string,documents: bool}>} $args
     *
     * @return array{result: bool}
     */
    public function __invoke(mixed $root, array $args): array {
        foreach ($args['input'] as $input) {
            $this->container
                ->make(AssetUpdate::class)
                ->init($input['id'], $input['documents'] ?? null)
                ->dispatch();
        }

        return [
            'result' => true,
        ];
    }
}
