<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Container;

/**
 * Indicates that the object should survive after call the
 * {@link \App\Services\DataLoader\Container\Container::forgetInstances()}.
 *
 * @internal
 */
interface SingletonPersistent {
    // empty
}
