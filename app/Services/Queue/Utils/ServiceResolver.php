<?php declare(strict_types = 1);

namespace App\Services\Queue\Utils;

use App\Services\Queue\CronJob;
use App\Services\Queue\Exceptions\ServiceNotFound;
use App\Services\Queue\Queue;
use App\Services\Settings\Settings;
use Illuminate\Contracts\Container\Container;

use function is_null;

class ServiceResolver {
    public function __construct(
        protected Container $container,
        protected Settings $settings,
        protected Queue $queue,
    ) {
        // empty
    }

    public function get(string $name): CronJob {
        $services = $this->settings->getServices();
        $service  = null;

        foreach ($services as $class) {
            $instance = $this->container->make($class);

            if ($this->queue->getName($instance) === $name) {
                $service = $instance;
                break;
            }
        }

        if (is_null($service)) {
            throw new ServiceNotFound($name);
        }

        return $service;
    }
}
