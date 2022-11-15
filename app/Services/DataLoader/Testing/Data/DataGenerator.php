<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

use Exception;
use Faker\Generator as Faker;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Utils\WithTestData;
use Symfony\Component\Filesystem\Filesystem;

use function assert;
use function dirname;
use function json_encode;
use function ksort;
use function sort;
use function sprintf;

use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_LINE_TERMINATORS;
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
     * @param class-string<Data> $class
     */
    public function generate(string $class): bool {
        // Exists?
        $fs          = new Filesystem();
        $contextName = self::CONTEXT;
        $contextRoot = $this->getTestData($class);
        $contextFile = $contextRoot->file($contextName);
        $contextPath = dirname($contextFile->getPathname());

        if ($contextFile->isFile()) {
            return true;
        }

        // Dir?
        $fs->mkdir($contextPath);

        // Generate
        $db   = $this->app->make('db');
        $data = $this->app->make($class);

        try {
            $db->beginTransaction();

            $result = $data->generate($contextRoot);
        } finally {
            $db->rollBack();
        }

        if ($result === null) {
            throw new Exception(sprintf(
                'Failed to generate test data for `%s`.',
                $class,
            ));
        }

        // Save context
        $contextData    = $result->toArray();
        $contextDefault = [
            'generated' => Date::now(),
        ];

        ksort($contextData);

        foreach ($contextData as &$value) {
            sort($value);
        }

        $fs->dumpFile($contextFile->getPathname(), json_encode(
            $contextDefault + $contextData,
            JSON_PRETTY_PRINT
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_LINE_TERMINATORS
            | JSON_PRESERVE_ZERO_FRACTION
            | JSON_THROW_ON_ERROR,
        ));

        // Return
        return true;
    }

    /**
     * @param class-string<Data> $class
     */
    public function restore(string $class): bool {
        $contextName = self::CONTEXT;
        $contextRoot = $this->getTestData($class);
        $contextData = $contextRoot->json($contextName);
        $context     = new Context($contextData);
        $data        = $this->app->make($class);

        return $data->restore($contextRoot, $context);
    }
}
