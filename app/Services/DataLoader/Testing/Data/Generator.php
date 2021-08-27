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
use function json_encode;
use function sprintf;

class Generator {
    use WithTestData;

    public const MARKER = 'data.json';

    public function __construct(
        protected Application $app,
        protected Faker $faker,
    ) {
        // empty
    }

    /**
     * @template T of \App\Services\DataLoader\Testing\Data\Data
     *
     * @param class-string<T> $class
     *
     * @return class-string<T>
     */
    public function generate(string $class): string {
        // Exists?
        $marker = $this->getTestData($class)->file('/'.self::MARKER);
        $path   = dirname($marker->getPathname());
        $fs     = new Filesystem();

        if ($marker->isFile()) {
            return $class;
        }

        // Cleanup
        $fs->mkdir($path);
        $fs->remove((new Finder())->in($path));

        // Generate
        $result = $this->app->make($class)->generate($path);

        if (!$result) {
            throw new Exception(sprintf(
                'Failed to generate test data for `%s`.',
                $class,
            ));
        }

        // Add marker
        $fs->dumpFile($marker->getPathname(), json_encode([
            'generated' => Date::now(),
        ]));

        // Return
        return $class;
    }
}
