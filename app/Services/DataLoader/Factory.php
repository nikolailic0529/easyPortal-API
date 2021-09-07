<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Services\DataLoader\Container\Isolated;
use App\Services\DataLoader\Exceptions\FactorySearchModeException;
use Closure;
use Psr\Log\LoggerInterface;

/**
 * Factories implement logic on how to create an application's model from an
 * external entity.
 *
 * Important notes:
 * - factories must not cache anything
 *
 * @internal
 */
abstract class Factory implements Isolated {
    private bool $searchMode = false;

    public function __construct(
        protected LoggerInterface $logger,
        protected Normalizer $normalizer,
    ) {
        // empty
    }

    protected function getLogger(): LoggerInterface {
        return $this->logger;
    }

    protected function getNormalizer(): Normalizer {
        return $this->normalizer;
    }

    protected function isSearchMode(): bool {
        return $this->searchMode;
    }

    protected function setSearchMode(bool $searchMode): void {
        $this->searchMode = $searchMode;
    }

    protected function inSearchMode(Closure $closure): mixed {
        $mode = $this->isSearchMode();

        $this->setSearchMode(true);

        try {
            return $closure();
        } catch (FactorySearchModeException) {
            return null;
        } finally {
            $this->setSearchMode($mode);
        }
    }

    protected function factory(Closure $closure): Closure {
        if ($this->isSearchMode()) {
            $closure = static function (): void {
                throw new FactorySearchModeException();
            };
        }

        return $closure;
    }
}
