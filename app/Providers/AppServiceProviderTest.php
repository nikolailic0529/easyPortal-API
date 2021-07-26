<?php declare(strict_types = 1);

namespace App\Providers;

use App\Models\Model;
use Composer\Autoload\ClassMapGenerator;
use Illuminate\Contracts\Config\Repository;
use ReflectionClass;
use Tests\TestCase;
use Throwable;

use function base_path;
use function implode;

use const PHP_EOL;

/**
 * @internal
 * @coversDefaultClass \App\Providers\AppServiceProvider
 */
class AppServiceProviderTest extends TestCase {
    /**
     * @covers ::bootMorphMap
     */
    public function testBootMorphMap(): void {
        // Search all models
        $models      = [];
        $directories = $this->app->get(Repository::class)->get('ide-helper.model_locations', []);

        foreach ($directories as $directory) {
            $classes = ClassMapGenerator::createMap(base_path($directory));

            foreach ($classes as $class => $path) {
                $class = new ReflectionClass($class);

                if (!$class->isSubclassOf(Model::class)) {
                    continue;
                }

                if ($class->isTrait() || $class->isAbstract()) {
                    continue;
                }

                $models[] = $class;
            }
        }

        // Search missed
        $missed = [];

        foreach ($models as $model) {
            try {
                if ($model->getName() === $model->newInstance()->getMorphClass()) {
                    $missed[] = $model->getName();
                }
            } catch (Throwable) {
                $missed[] = $model->getName();
            }
        }

        // Assert
        $message = 'Following models missed in MorphMap:'.PHP_EOL.'- '.implode(PHP_EOL.'- ', $missed).PHP_EOL;

        $this->assertEmpty($missed, $message);
    }
}
