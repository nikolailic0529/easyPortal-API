<?php declare(strict_types = 1);

namespace Tests;

use Closure;
use LogicException;
use Mockery;
use Mockery\Exception\InvalidCountException;
use OutOfBoundsException;

use function sprintf;

/**
 * @mixin \Tests\TestCase
 */
trait Override {
    /**
     * @var array<class-string,\Mockery\MockInterface>
     */
    private array $overrides = [];

    protected function assertPostConditions(): void {
        foreach ($this->overrides as $class => $spy) {
            try {
                $spy->shouldHaveBeenCalled();
            } catch (InvalidCountException $exception) {
                throw new OutOfBoundsException(sprintf(
                    'Override for `%s` should be used at least 1 times but used 0 times.',
                    $class,
                ));
            }
        }

        parent::assertPostConditions();
    }

    /**
     * @template T
     *
     * @param class-string<T>                                            $class
     * @param null|Closure(T|\Mockery\MockInterface, \Tests\TestCase): T $factory
     *
     * @return T|\Mockery\MockInterface
     */
    protected function override(string $class, Closure $factory = null): mixed {
        // Overridden?
        if (isset($this->overrides[$class])) {
            throw new LogicException(sprintf(
                'Override for `%s` already defined.',
                $class,
            ));
        }

        // Mock
        $mock = Mockery::mock($class);

        if ($factory) {
            $mock = $factory($mock, $this) ?: $mock;
        }

        // Override
        $this->overrides[$class] = Mockery::spy(static function () use ($mock): mixed {
            return $mock;
        });

        $this->app->bind($class, Closure::fromCallable($this->overrides[$class]));

        // Return
        return $mock;
    }
}
