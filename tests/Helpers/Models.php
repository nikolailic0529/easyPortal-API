<?php declare(strict_types = 1);

namespace Tests\Helpers;

use Closure;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;

use function array_fill_keys;
use function config;

/**
 * Holds list of all application models which use default connection.
 */
class Models {
    /**
     * @param \Closure(\ReflectionClass<\Illuminate\Database\Eloquent\Model>): bool $filter
     *
     * @return array<class-string<\Illuminate\Database\Eloquent\Model>,\ReflectionClass<\Illuminate\Database\Eloquent\Model>>
     */
    public static function get(Closure $filter = null): array {
        return ClassMap::get(static function (ReflectionClass $class) use ($filter): bool {
            // Model?
            if (!$class->isSubclassOf(Model::class)) {
                return false;
            }

            // Class?
            if ($class->isTrait() || $class->isAbstract()) {
                return false;
            }

            // Connection?
            if ($class->newInstance()->getConnectionName() !== null) {
                return false;
            }

            // Ignored?
            $ignored = array_fill_keys(config('ide-helper.ignored_models', []), true);

            if (isset($ignored[$class->getName()])) {
                return false;
            }

            // Filter?
            return !$filter || $filter($class);
        });
    }
}
