<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Container;

use Illuminate\Container\Container as IlluminateContainer;

use function is_a;

/**
 * Special container to create Resolvers/Factories/Loaders.
 *
 * The main reasons why it created are
 * - only one instance of each Provider must exist;
 * - instances must be destroyed after data loading;
 * - constructors may require a lot of arguments, this is really annoying to
 *   pass them.
 *
 * The second reason prevents us to use standard singletons - Laravel will not
 * destroy them after the job finished thus the memory will not be released.
 *
 * @internal
 */
class Container extends IlluminateContainer {
    public function __construct(
        protected IlluminateContainer $root,
    ) {
        // empty
    }

    /**
     * @template T
     *
     * @param class-string<T> $abstract
     * @param array<mixed>    $parameters
     *
     * @return T
     */
    public function make($abstract, array $parameters = []): object {
        return parent::make($abstract, $parameters);
    }

    /**
     * @inheritdoc
     */
    public function resolve($abstract, $parameters = [], $raiseEvents = true) {
        $resolved = null;

        if (is_a($abstract, Singleton::class, true) && !$this->bound($abstract)) {
            $this->singleton($abstract);
        }

        if ($this->bound($abstract)) {
            $resolved = parent::resolve($abstract, $parameters, $raiseEvents);
        } elseif (is_a($abstract, Isolated::class, true)) {
            $resolved = parent::resolve($abstract, $parameters, $raiseEvents);
        } elseif ($abstract === self::class) {
            $resolved = $this;
        } else {
            // For external objects, we use the standard container, but there
            // is one potential pitfall: if the standard object injects our
            // internal object it will receive a fresh instance.
            //
            // All our objects are internal, why do you want to inject them?
            $resolved = $this->root->resolve($abstract, $parameters, $raiseEvents);
        }

        return $resolved;
    }
}
