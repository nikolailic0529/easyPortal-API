<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

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
abstract class Factory {
    private bool $searchMode = false;

    public function __construct(
        protected LoggerInterface $logger,
        protected Normalizer $normalizer,
    ) {
        // empty
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
        } catch (FactoryObjectNotFoundException) {
            return null;
        } finally {
            $this->setSearchMode($mode);
        }
    }

    protected function factory(Closure $closure): Closure {
        if ($this->isSearchMode()) {
            $closure = static function (): void {
                throw new FactoryObjectNotFoundException();
            };
        }

        return $closure;
    }
}
