<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Document;

use App\Services\DataLoader\Jobs\DocumentUpdate;
use Illuminate\Contracts\Container\Container;

class Sync {
    public function __construct(
        protected Container $container,
    ) {
        // empty
    }

    /**
     * @param array{input: array<array{id: string}>} $args
     *
     * @return array{result: bool}
     */
    public function __invoke(mixed $root, array $args): array {
        foreach ($args['input'] as $input) {
            $this->container
                ->make(DocumentUpdate::class)
                ->init($input['id'])
                ->dispatch();
        }

        return [
            'result' => true,
        ];
    }
}
