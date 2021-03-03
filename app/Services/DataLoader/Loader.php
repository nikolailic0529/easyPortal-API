<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use Psr\Log\LoggerInterface;

/**
 * Load data from API and create app's objects. You must use
 * {@link \App\Services\DataLoader\DataLoaderService} to obtain instance.
 *
 * @internal
 */
abstract class Loader {
    public function __construct(
        protected LoggerInterface $logger,
        protected Client $client,
    ) {
        // empty
    }
}
