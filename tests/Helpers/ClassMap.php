<?php declare(strict_types = 1);

namespace Tests\Helpers;

use Composer\ClassMapGenerator\ClassMapGenerator;
use Illuminate\Support\Collection;
use ReflectionClass;

use function app_path;
use function class_exists;
use function str_ends_with;

/**
 * Holds list of all application classes.
 */
class ClassMap {
    /**
     * @var array<class-string<object>,ReflectionClass<object>>
     */
    protected static array $classes;

    /**
     * @return Collection<class-string<object>,ReflectionClass<object>>
     */
    public static function get(): Collection {
        // Cached?
        self::$classes ??= self::load();
        $classes         = new Collection(self::$classes);

        // Return
        return $classes;
    }

    /**
     * @return array<class-string<object>,ReflectionClass<object>>
     */
    protected static function load(): array {
        self::$classes = [];
        $directories   = [
            app_path(),
        ];

        foreach ($directories as $directory) {
            $classes = ClassMapGenerator::createMap($directory);

            foreach ($classes as $class => $path) {
                // Test?
                if (str_ends_with($path, 'Test.php')) {
                    continue;
                }

                // Exists?
                if (!class_exists($class)) {
                    continue;
                }

                // Ok
                self::$classes[$class] = new ReflectionClass($class);
            }
        }

        return self::$classes;
    }
}
