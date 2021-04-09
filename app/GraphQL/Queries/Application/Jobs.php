<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Jobs\NamedJob;
use App\Services\Settings\Description;
use App\Services\Settings\Settings as SettingsService;
use Illuminate\Contracts\Foundation\Application;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;
use LastDragon_ru\LaraASP\Queue\Queueables\Job;
use ReflectionClass;

use function __;
use function array_values;

class Jobs {
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
        $jobs         = [];
        $configurator = $this->app->make(QueueableConfigurator::class);

        foreach ($this->settings->getJobs() as $class) {
            $job    = $this->app->make($class);
            $config = $configurator->config($job);

            $jobs[$class] = [
                'name'        => $this->getName($job),
                'description' => $this->getDescription($job),
                'queue'       => $config->get('queue'),
                'settings'    => [],
            ];
        }

        // Collect settings
        $settings = $this->settings->getEditableSettings();

        foreach ($settings as $setting) {
            $class = $setting->getJob();

            if ($class && isset($jobs[$class])) {
                $jobs[$class]['settings'][] = $setting->getName();
            }
        }

        // Return
        return array_values($jobs);
    }

    protected function getName(Job $job): string {
        return $job instanceof NamedJob
            ? $job->displayName()
            : $job::class;
    }

    protected function getDescription(Job $job): ?string {
        $key  = "settings.jobs.{$this->getName($job)}";
        $desc = __($key);

        if ($key === $desc) {
            $desc = (new Description())->get(new ReflectionClass($job));
        }

        return $desc;
    }
}
