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
     * @param array{input: array<array{id: string, assets?: ?bool}>} $args
     *
     * @return array{result: bool}
     */
    public function __invoke(mixed $root, array $args): array {
        foreach ($args['input'] as $input) {
            $this->container
                ->make(DocumentSync::class)
                ->init($input['id'])
                ->run();

            if (isset($input['assets']) && $input['assets']) {
                $document = Document::query()->whereKey($input['id'])->first();
                $assets   = $document?->assets()->getQuery()->getChunkedIterator();

                foreach ($assets ?? [] as $asset) {
                    $this->container
                        ->make(AssetSync::class)
                        ->init($asset->getKey(), false)
                        ->run();
                }
            }
        }

        return [
            'result' => true,
        ];
    }
}
