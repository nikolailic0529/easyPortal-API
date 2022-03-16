<?php declare(strict_types = 1);

namespace Tests\Helpers;

use App\Services\Logger\Logger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use ReflectionClass;

use function array_fill_keys;
use function config;

/**
 * Get list of all application models.
 */
class Models {
    /**
     * @return Collection<class-string<Model>,ReflectionClass<Model>>
     */
    public static function get(): Collection {
        $ignored = array_fill_keys(config('ide-helper.ignored_models', []), true);

        return ClassMap::get()->filter(static function (ReflectionClass $class) use ($ignored): bool {
            return $class->isSubclassOf(Model::class)
                && !$class->isTrait()
                && !$class->isAbstract()
                && $class->newInstance()->getConnectionName() !== Logger::CONNECTION
                && !isset($ignored[$class->getName()]);
        });
    }
}
