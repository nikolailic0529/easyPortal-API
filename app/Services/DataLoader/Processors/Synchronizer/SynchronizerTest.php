<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Synchronizer;

use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Processors\Importer\Importer;
use App\Services\Organization\Eloquent\OwnedByScope;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use App\Utils\Iterators\Contracts\MixedIterator;
use Illuminate\Support\Facades\Date;
use Mockery;
use ReflectionClass;
use Tests\Helpers\ClassMap;
use Tests\TestCase;

use function array_map;
use function array_unique;
use function assert;
use function json_encode;
use function sprintf;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Processors\Synchronizer\Synchronizer
 */
class SynchronizerTest extends TestCase {
    public function testOperationsRespectSettings(): void {
        $invalid = GlobalScopes::callWithout(OwnedByScope::class, function (): array {
            $iterator = Mockery::mock(MixedIterator::class);
            $invalid  = [];
            $loaders  = ClassMap::get()
                ->filter(static function (ReflectionClass $class): bool {
                    return $class->isSubclassOf(Synchronizer::class)
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

                assert($loader instanceof Synchronizer);

                foreach ([true, false] as $force) {
                    // State
                    $state = $loader->restoreState([
                        'from'    => Date::now(),
                        'force'   => $force,
                        'started' => Date::now(),
                    ]);

                    // Processor
                    $processor = $loader->getProcessor($state);

                    if (
                        !($processor instanceof Importer)
                        || $processor->isForce() !== $state->force
                        || $processor->getFrom() !== $state->from
                    ) {
                        $invalid[$class][] = 'getProcessor()';
                    }

                    // Outdated Processor
                    $outdatedProcessor = $loader->getOutdatedProcessor($state, $iterator);

                    if (
                        !($outdatedProcessor instanceof Importer)
                        || $outdatedProcessor->isForce() !== $state->force
                    ) {
                        $invalid[$class][] = 'getOutdatedProcessor()';
                    }
                }
            }

            $invalid = array_map(static fn(array $o) => array_unique($o), $invalid);

            return $invalid;
        });

        self::assertEmpty($invalid, sprintf(
            'The following methods do not respect the settings: %s',
            json_encode($invalid, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
        ));
    }
}
