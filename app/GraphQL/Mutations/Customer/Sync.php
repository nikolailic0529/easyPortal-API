<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Customer;

use App\Services\DataLoader\Jobs\CustomerSync;
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
                ->make(CustomerSync::class)
                ->init(
                    id             : $id,
                    warrantyCheck  : true,
                    assets         : true,
                    assetsDocuments: true,
                )
                ->run();
        }

        return [
            'result' => true,
        ];
    }
}
