<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Exceptions\TranslatedException;
use App\Jobs\NamedJob;
use App\Services\Settings\Settings;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use LastDragon_ru\LaraASP\Queue\Queueables\CronJob;

use function is_null;

class DispatchApplicationService {
    public function __construct(
        protected Application $app,
        protected Settings $settings,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     *
     * @return array{setting: array<mixed>}
     */
    public function __invoke($_, array $args): array {
        $result      = false;
        $service     = $this->getService($args['input']['name'] ?? '');
        $immediately = $args['input']['immediately'] ?? false;

        if (is_null($service)) {
            throw new DispatchApplicationServiceNotFoundException();
        }

        try {
            if ($immediately) {
                $result = (bool) $service->run();
            } else {
                $result = (bool) $service->dispatch();
            }
        } catch (TranslatedException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            throw new DispatchApplicationServiceFailed($exception);
        }

        return [
            'result' => $result,
        ];
    }

    protected function getService(string $name): ?CronJob {
        $services = $this->settings->getServices();
        $service  = null;

        foreach ($services as $class) {
            // By class?
            if ($class === $name) {
                $service = $this->app->make($class);
                break;
            }

            // By name
            $instance = $this->app->make($class);

            if ($instance instanceof NamedJob && $instance->displayName() === $name) {
                $service = $instance;
                break;
            }
        }

        return $service;
    }
}
