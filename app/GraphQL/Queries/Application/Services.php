<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\Queue\Contracts\Progressable;
use App\Services\Queue\Contracts\Stoppable;
use App\Services\Queue\CronJob;
use App\Services\Queue\Queue;
use App\Services\Settings\Settings as SettingsService;
use App\Utils\Cast;
use App\Utils\Description;
use Illuminate\Contracts\Foundation\Application;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;
use ReflectionClass;

use function array_values;
use function reset;
use function trans;
use function usort;

class Services {
    public function __construct(
        protected Application $app,
        protected SettingsService $settings,
        protected Queue $queue,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array<int,mixed>
     */
    public function __invoke(mixed $root, array $args): array {
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
                'enabled'      => (bool) $config->get('enabled'),
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

        // Sort
        usort($services, static function (array $a, array $b): int {
            return $a['name'] <=> $b['name'];
        });

        // Return
        return array_values($services);
    }

    protected function getDescription(CronJob $service): ?string {
        $key  = "settings.services.{$this->queue->getName($service)}";
        $desc = Cast::toString(trans($key));

        if ($key === $desc) {
            $desc = (new Description())->get(new ReflectionClass($service));
        }

        return $desc;
    }
}
