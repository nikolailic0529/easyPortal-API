<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

use Exception;
use Faker\Generator as Faker;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Utils\WithTestData;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use function dirname;
use function is_array;
use function json_encode;
use function sprintf;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class DataGenerator {
    use WithTestData;

    public const CONTEXT = 'context.json';

    public function __construct(
        protected Application $app,
        protected Faker $faker,
    ) {
        // empty
    }

    /**
     * @param class-string<\App\Services\DataLoader\Testing\Data\Data> $class
     */
    public function generate(string $class): bool {
        // Exists?
        $fs          = new Filesystem();
        $data        = $this->app->make($class);
        $contextName = self::CONTEXT;
        $contextFile = $this->getTestData($class)->file($contextName);
        $contextPath = dirname($contextFile->getPathname());

        if ($contextFile->isFile()) {
            return true;
        }

        // Cleanup
        $fs->mkdir($contextPath);
        $fs->remove((new Finder())->in($contextPath));

        // Generate
        $db = $this->app->make('db');

        try {
            $db->beginTransaction();

            $contextDataData = $data->generate($contextPath);
        } finally {
            $db->rollBack();
        }

        if ($contextDataData === false) {
            throw new Exception(sprintf(
                'Failed to generate test data for `%s`.',
                $class,
            ));
        }

        // Save context
        $options     = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        $contextData = [
            'generated' => Date::now(),
        ];

        if (is_array($contextDataData)) {
            $contextData += $contextDataData;
        }

        $fs->dumpFile($contextFile->getPathname(), json_encode($contextData, $options));

        // Return
        return true;
    }

    /**
     * @param class-string<\App\Services\DataLoader\Testing\Data\Data> $class
     */
    public function restore(string $class): bool {
        $data        = $this->app->make($class);
        $contextName = self::CONTEXT;
        $contextFile = $this->getTestData($class)->file($contextName);
        $contextPath = dirname($contextFile->getPathname());
        $contextData = $this->getTestData($class)->json($contextName);

        return $data->restore($contextPath, $contextData);
    }
}