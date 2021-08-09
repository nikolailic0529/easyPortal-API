<?php declare(strict_types = 1);

namespace Tests;

use Closure;
use Composer\Autoload\ClassMapGenerator;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;

use function array_filter;
use function base_path;
use function config;
use function str_ends_with;

/**
 * Holds list of all application models which use default connection.
 */
class Models {
    /**
     * @var array<class-string<\Illuminate\Database\Eloquent\Model>,\ReflectionClass<\Illuminate\Database\Eloquent\Model>>
     */
    protected static array $models;

    /**
     * @param \Closure(\ReflectionClass<\Illuminate\Database\Eloquent\Model>): bool $filter
     *
     * @return array<class-string<\Illuminate\Database\Eloquent\Model>,\ReflectionClass<\Illuminate\Database\Eloquent\Model>>
     */
    public static function get(Closure $filter = null): array {
        // Cached?
        self::$models ??= self::search();
        $models       = self::$models;

        // Filter
        if ($filter) {
            $models = array_filter($models, $filter);
        }

        // Return
        return $models;
    }

    /**
     * @return array<\Illuminate\Database\Eloquent\Model>
     */
    protected static function search(): array {
        $models      = [];
        $directories = config('ide-helper.model_locations', []);

        foreach ($directories as $directory) {
            $classes = ClassMapGenerator::createMap(base_path($directory));

            foreach ($classes as $class => $path) {
                $class = new ReflectionClass($class);

                // Model?
                if (!$class->isSubclassOf(Model::class)) {
                    continue;
                }

                // Class?
                if ($class->isTrait() || $class->isAbstract()) {
                    continue;
                }

                // Test?
                if (str_ends_with($class->getFileName(), 'Test.php')) {
                    continue;
                }

                // Connection?
                if ($class->newInstance()->getConnectionName() !== null) {
                    continue;
                }

                // Ok
                $models[$class->getName()] = $class;
            }
        }

        return $models;
    }
}
