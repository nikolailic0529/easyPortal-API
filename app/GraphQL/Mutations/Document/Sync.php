<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Document;

use App\Models\Document;
use App\Services\DataLoader\Jobs\DocumentSync;
use Illuminate\Contracts\Container\Container;

class Sync {
    public function __construct(
        protected Container $container,
    ) {
        // empty
    }

    /**
     * @return array{result: bool, assets: bool}
     */
    public function __invoke(Document $document): array {
        $job    = $this->container->make(DocumentSync::class)->init($document);
        $result = $this->container->call($job);

        return $result;
    }
}
