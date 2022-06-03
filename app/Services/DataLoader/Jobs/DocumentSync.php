<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Document;
use App\Services\DataLoader\Loader\Loaders\DocumentLoader;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Exception;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;

class DocumentSync extends Sync {
    public function displayName(): string {
        return 'ep-data-loader-document-sync';
    }

    public function init(Document $document): static {
        return $this
            ->setObjectId($document->getKey())
            ->initialized();
    }

    /**
     * @return array{result: bool, assets: bool}
     */
    public function __invoke(ExceptionHandler $handler, Container $container, DocumentLoader $loader): array {
        return GlobalScopes::callWithoutAll(function () use ($container, $handler, $loader): array {
            $result = $this->syncProperties($handler, $loader);
            $assets = $result && $this->syncAssets($handler, $container);

            return [
                'result' => $result,
                'assets' => $assets,
            ];
        });
    }

    protected function syncProperties(ExceptionHandler $handler, DocumentLoader $loader): bool {
        try {
            return $loader
                ->setObjectId($this->getObjectId())
                ->start();
        } catch (Exception $exception) {
            $handler->report($exception);
        }

        return false;
    }

    protected function syncAssets(ExceptionHandler $handler, Container $container): bool {
        // todo(DataLoader): Seems would be good to use Batches?

        try {
            $result   = true;
            $document = Document::query()->whereKey($this->getObjectId())->first();
            $assets   = $document ? $document->assets : [];

            foreach ($assets as $asset) {
                $job    = $container->make(AssetSync::class)->init($asset);
                $result = ($container->call($job)['result'] ?? false) && $result;
            }
        } catch (Exception $exception) {
            $handler->report($exception);

            $result = false;
        }

        return $result;
    }
}
