<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\Queue\Job;
use App\Services\Queue\Queue;
use App\Services\Settings\Settings as SettingsService;
use App\Utils\Cast;
use App\Utils\Description;
use Illuminate\Contracts\Foundation\Application;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;
use ReflectionClass;

use function array_values;
use function trans;
use function usort;

class Jobs {
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
        $jobs         = [];
        $configurator = $this->app->make(QueueableConfigurator::class);

        foreach ($this->settings->getJobs() as $class) {
            $job    = $this->app->make($class);
            $config = $configurator->config($job);

            $jobs[$class] = [
                'name'        => $this->queue->getName($job),
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

        // Sort
        usort($jobs, static function (array $a, array $b): int {
            return $a['name'] <=> $b['name'];
        });

        // Return
        return array_values($jobs);
    }

    protected function getDescription(Job $job): ?string {
        $key  = "settings.jobs.{$this->queue->getName($job)}";
        $desc = Cast::toString(trans($key));

        if ($key === $desc) {
            $desc = (new Description())->get(new ReflectionClass($job));
        }

        return $desc;
    }
}
