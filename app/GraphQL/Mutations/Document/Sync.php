<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Document;

use App\Models\Document;
use App\Services\DataLoader\Jobs\AssetUpdate;
use App\Services\DataLoader\Jobs\DocumentUpdate;
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
                ->make(DocumentUpdate::class)
                ->init($input['id'])
                ->dispatch();

            if (isset($input['assets']) && $input['assets']) {
                $document = Document::query()->whereKey($input['id'])->first();
                $assets   = $document?->assets()->getQuery()->getChunkedIterator();

                foreach ($assets ?? [] as $asset) {
                    $this->container
                        ->make(AssetUpdate::class)
                        ->init($asset->getKey(), false)
                        ->dispatch();
                }
            }
        }

        return [
            'result' => true,
        ];
    }
}
