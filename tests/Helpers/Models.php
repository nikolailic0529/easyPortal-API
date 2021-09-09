<?php declare(strict_types = 1);

namespace Tests\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use ReflectionClass;

use function array_fill_keys;
use function config;

/**
 * Get list of all application models which use default connection.
 */
class Models {
    /**
     * @return \Illuminate\Support\Collection<class-string<\Illuminate\Database\Eloquent\Model>,\ReflectionClass<\Illuminate\Database\Eloquent\Model>>
     */
    public static function get(): Collection {
        return ClassMap::get()->filter(static function (ReflectionClass $class): bool {
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

            // Ok
            return true;
        });
    }
}
