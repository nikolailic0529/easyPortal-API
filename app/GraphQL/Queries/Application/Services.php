<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\Queue\CronJob;
use App\Services\Queue\Progressable;
use App\Services\Queue\Queue;
use App\Services\Queue\Stoppable;
use App\Services\Settings\Description;
use App\Services\Settings\Settings as SettingsService;
use Illuminate\Contracts\Foundation\Application;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;
use ReflectionClass;

use function __;
use function array_values;
use function reset;

class Services {
    public function __construct(
        protected Application $app,
        protected SettingsService $settings,
        protected Queue $queue,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     *
     * @return array<string,mixed>
     */
    public function __invoke($_, array $args): array {
        // Collect properties
        $services     = [];
        $instances    = [];
        $configurator = $this->app->make(QueueableConfigurator::class);

        foreach ($this->settings->getServices() as $class) {
            $service = $this->app->make($class);
            $config  = $configurator->config($service);
            $name    = $this->queue->getName($service);

            $instances[$class] = $service;
            $services[$name]   = [
                'name'         => $name,
                'description'  => $this->getDescription($service),
                'enabled'      => $config->get('enabled'),
                'cron'         => $config->get('cron'),
                'queue'        => $config->get('queue'),
                'settings'     => [],
                'state'        => null,
                'progress'     => $this->queue->getProgress($service),
                'stoppable'    => $service instanceof Stoppable,
                'progressable' => $service instanceof Progressable,
            ];
        }

        // Collect settings
        $settings = $this->settings->getEditableSettings();

        foreach ($settings as $setting) {
            $class   = $setting->getService();
            $service = $instances[$class] ?? null;

            if ($service) {
                $services[$this->queue->getName($service)]['settings'][] = $setting->getName();
            }
        }

        // State
        $states = $this->queue->getStates($instances);

        foreach ($states as $name => $state) {
            $services[$name]['state'] = reset($state) ?: null;
        }

        // Return
        return array_values($services);
    }

    protected function getDescription(CronJob $service): ?string {
        $key  = "settings.services.{$this->queue->getName($service)}";
        $desc = __($key);

        if ($key === $desc) {
            $desc = (new Description())->get(new ReflectionClass($service));
        }

        return $desc;
    }
}
