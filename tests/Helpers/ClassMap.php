<?php declare(strict_types = 1);

namespace Tests\Helpers;

use Composer\Autoload\ClassMapGenerator;
use Illuminate\Database\Eloquent\Model;
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
     * @var array<class-string,ReflectionClass>
     */
    protected static array $classes;

    /**
     * @return Collection<class-string,ReflectionClass>
     */
    public static function get(): Collection {
        // Cached?
        self::$classes ??= self::load();
        $classes         = new Collection(self::$classes);

        // Return
        return $classes;
    }

    /**
     * @return array<Model>
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
