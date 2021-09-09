<?php declare(strict_types = 1);

namespace Tests\Helpers;

use Closure;
use Composer\Autoload\ClassMapGenerator;
use ReflectionClass;

use function app_path;
use function array_filter;
use function base_path;
use function class_exists;
use function database_path;
use function str_ends_with;

class ClassMap {
    /**
     * @var array<class-string,\ReflectionClass>
     */
    protected static array $classes;

    /**
     * @param \Closure(\ReflectionClass): bool $filter
     *
     * @return array<class-string,\ReflectionClass>
     */
    public static function get(Closure $filter = null): array {
        // Cached?
        self::$classes ??= self::load();
        $classes         = self::$classes;

        // Filter
        if ($filter) {
            $classes = array_filter($classes, $filter);
        }

        // Return
        return $classes;
    }

    /**
     * @return array<\Illuminate\Database\Eloquent\Model>
     */
    protected static function load(): array {
        self::$classes = [];
        $directories   = [
            app_path(),
        ];

        foreach ($directories as $directory) {
            $classes = ClassMapGenerator::createMap($directory);

            foreach ($classes as $class => $path) {
                // Exists?
                if (!class_exists($class)) {
                    continue;
                }

                // Test?
                if (str_ends_with($path, 'Test.php')) {
                    continue;
                }

                // Ok
                self::$classes[$class] = new ReflectionClass($class);
            }
        }

        return self::$classes;
    }
}
