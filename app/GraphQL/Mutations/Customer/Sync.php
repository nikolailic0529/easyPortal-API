<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Customer;

use App\Services\DataLoader\Jobs\CustomerSync;
use Illuminate\Contracts\Container\Container;
use Throwable;

use function array_unique;
use function count;

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
        $ids    = array_unique($args['input']['id']);
        $failed = [];

        foreach ($ids as $id) {
            try {
                $this->container
                    ->make(CustomerSync::class)
                    ->init(
                        id             : $id,
                        warrantyCheck  : true,
                        assets         : true,
                        assetsDocuments: true,
                    )
                    ->run();
            } catch (Throwable) {
                $failed[] = $id;
            }
        }

        return [
            'result' => count($failed) === 0,
            'failed' => $failed,
        ];
    }
}
