<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders\Concerns;

use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Exceptions\FailedToUpdateCalculatedProperties;
use App\Services\DataLoader\Jobs\CustomersRecalculate;
use App\Services\DataLoader\Jobs\LocationsRecalculate;
use App\Services\DataLoader\Jobs\ResellersRecalculate;
use App\Services\DataLoader\Resolver\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolver\Resolvers\LocationResolver;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use Illuminate\Contracts\Debug\ExceptionHandler;
use InvalidArgumentException;
use Throwable;

trait CalculatedProperties {
    abstract protected function getExceptionHandler(): ExceptionHandler;

    abstract protected function getContainer(): Container;

    /**
     * @param class-string<\App\Services\DataLoader\Resolver\Resolver> ...$resolvers
     */
    protected function updateCalculatedProperties(string ...$resolvers): void {
        foreach ($resolvers as $resolver) {
            // Empty?
            $resolver = $this->getContainer()->make($resolver);
            $objects  = $resolver->getResolved();

            if ($objects->isEmpty()) {
                continue;
            }

            // Update
            try {
                $job = null;

                if ($resolver instanceof ResellerResolver) {
                    $job = ResellersRecalculate::class;
                } elseif ($resolver instanceof CustomerResolver) {
                    $job = CustomersRecalculate::class;
                } elseif ($resolver instanceof LocationResolver) {
                    $job = LocationsRecalculate::class;
                } else {
                    throw new InvalidArgumentException('Unsupported resolver.');
                }

                $this->getContainer()
                    ->make($job)
                    ->setModels($objects)
                    ->dispatch();
            } catch (Throwable $exception) {
                $this->getExceptionHandler()->report(
                    new FailedToUpdateCalculatedProperties($resolver, $objects, $exception),
                );
            }
        }
    }
}
