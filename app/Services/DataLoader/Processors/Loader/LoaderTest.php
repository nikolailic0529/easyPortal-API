<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Loader;

use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Processors\Importer\Importer;
use App\Services\DataLoader\Processors\Loader\Loaders\AssetLoader;
use App\Services\DataLoader\Processors\Loader\Loaders\CustomerLoader;
use App\Services\DataLoader\Processors\Loader\Loaders\ResellerLoader;
use App\Services\Organization\Eloquent\OwnedByScope;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Illuminate\Support\Facades\Date;
use Mockery;
use ReflectionClass;
use Tests\Helpers\ClassMap;
use Tests\TestCase;

use function array_map;
use function array_merge;
use function array_unique;
use function assert;
use function json_encode;
use function sprintf;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

/**
 * @internal
 * @covers \App\Services\DataLoader\Processors\Loader\Loader
 */
class LoaderTest extends TestCase {
    public function testOperationsRespectForce(): void {
        $invalid = GlobalScopes::callWithout(OwnedByScope::class, function (): array {
            $default = [
                'objectId' => $this->faker->uuid(),
            ];
            $states  = [
                AssetLoader::class    => array_merge($default, [
                    'withWarrantyCheck' => true,
                ]),
                CustomerLoader::class => array_merge($default, [
                    'started'           => Date::now(),
                    'withAssets'        => true,
                    'withDocuments'     => true,
                    'withWarrantyCheck' => true,
                ]),
                ResellerLoader::class => array_merge($default, [
                    'started'       => Date::now(),
                    'withAssets'    => true,
                    'withDocuments' => true,
                ]),
            ];
            $invalid = [];
            $loaders = ClassMap::get()
                ->filter(static function (ReflectionClass $class): bool {
                    return $class->isSubclassOf(Loader::class)
                        && !$class->isAbstract();
                })
                ->keys();

            foreach ($loaders as $class) {
                $loader = Mockery::mock($class);
                $loader->shouldAllowMockingProtectedMethods();
                $loader->makePartial();
                $loader
                    ->shouldReceive('getContainer')
                    ->atLeast()
                    ->once()
                    ->andReturn(
                        $this->app->make(Container::class),
                    );

                assert($loader instanceof Loader);

                foreach ([true, false] as $force) {
                    $state      = $loader->restoreState(array_merge($states[$class] ?? $default, [
                        'force' => $force,
                    ]));
                    $operations = $loader->getOperations($state);

                    foreach ($operations as $operation) {
                        $processor = $operation->getProcessor($state);

                        if ($processor instanceof CallbackLoader) {
                            continue;
                        }

                        if (!($processor instanceof Importer) || $processor->isForce() !== $state->force) {
                            $invalid[$class][] = $operation->getName();
                        }
                    }
                }
            }

            $invalid = array_map(static fn(array $o) => array_unique($o), $invalid);

            return $invalid;
        });

        self::assertEmpty($invalid, sprintf(
            'The following operations do not respect the `force` flag: %s',
            json_encode($invalid, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
        ));
    }
}
