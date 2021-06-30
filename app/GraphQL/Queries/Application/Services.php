<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\Queue\CronJob;
use App\Services\Settings\Description;
use App\Services\Settings\Settings as SettingsService;
use Illuminate\Contracts\Foundation\Application;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;
use ReflectionClass;

use function __;
use function array_values;

class Services {
    public function __construct(
        protected Application $app,
        protected SettingsService $settings,
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
        $configurator = $this->app->make(QueueableConfigurator::class);

        foreach ($this->settings->getServices() as $class) {
            $service = $this->app->make($class);
            $config  = $configurator->config($service);

            $services[$class] = [
                'name'        => $this->getName($service),
                'description' => $this->getDescription($service),
                'enabled'     => $config->get('enabled'),
                'cron'        => $config->get('cron'),
                'queue'       => $config->get('queue'),
                'settings'    => [],
            ];
        }

        // Collect settings
        $settings = $this->settings->getEditableSettings();

        foreach ($settings as $setting) {
            $class = $setting->getService();

            if ($class && isset($services[$class])) {
                $services[$class]['settings'][] = $setting->getName();
            }
        }

        // Return
        return array_values($services);
    }

    protected function getName(CronJob $service): string {
        return $service->displayName();
    }

    protected function getDescription(CronJob $service): ?string {
        $key  = "settings.services.{$this->getName($service)}";
        $desc = __($key);

        if ($key === $desc) {
            $desc = (new Description())->get(new ReflectionClass($service));
        }

        return $desc;
    }
}
