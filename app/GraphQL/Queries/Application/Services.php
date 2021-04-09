<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Jobs\NamedJob;
use App\Services\Settings\Description;
use App\Services\Settings\Settings as SettingsService;
use Illuminate\Contracts\Foundation\Application;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;
use LastDragon_ru\LaraASP\Queue\Queueables\CronJob;
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
        $configurator = $this->app->make(QueueableConfigurator::class);
        $settings     = $this->settings->getEditableSettings();
        $services     = [];

        foreach ($settings as $setting) {
            // Is Service?
            $class = $setting->getService();

            if (!$class) {
                continue;
            }

            // Add Setting
            if (isset($services[$class])) {
                $services[$class]['settings'][] = $setting->getName();
                continue;
            }

            // Collect info
            $service = $this->app->make($class);
            $config  = $configurator->config($service);

            $services[$class] = [
                'name'        => $this->getName($service),
                'description' => $this->getDescription($service),
                'enabled'     => $config->get('enabled'),
                'cron'        => $config->get('cron'),
                'queue'       => $config->get('queue'),
                'settings'    => [
                    $setting->getName(),
                ],
            ];
        }

        return array_values($services);
    }

    protected function getName(CronJob $service): string {
        return $service instanceof NamedJob
            ? $service->displayName()
            : $service::class;
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
