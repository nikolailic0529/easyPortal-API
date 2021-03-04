<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

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
    public function __construct(
        protected LoggerInterface $logger,
        protected Normalizer $normalizer,
    ) {
        // empty
    }
}
