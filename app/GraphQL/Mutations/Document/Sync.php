<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Document;

use App\Models\Document;
use App\Services\DataLoader\Jobs\AssetSync;
use App\Services\DataLoader\Jobs\DocumentSync;
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
                ->make(DocumentSync::class)
                ->init($id)
                ->run();

            $document = Document::query()->whereKey($id)->first();
            $assets   = $document?->assets()->getQuery()->getChunkedIterator();

            foreach ($assets ?? [] as $asset) {
                $this->container
                    ->make(AssetSync::class)
                    ->init(
                        id           : $asset->getKey(),
                        warrantyCheck: true,
                        documents    : false,
                    )
                    ->run();
            }
        }

        return [
            'result' => true,
        ];
    }
}
