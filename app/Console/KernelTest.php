<?php declare(strict_types = 1);

namespace App\Console;

use App\Console\Commands\TestCommand;
use App\Dev\IdeHelper\ModelsCommand;
use Illuminate\Console\Application;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel as KernelContract;
use ReflectionClass;
use Tests\Helpers\ClassMap;
use Tests\TestCase;

use function array_diff;
use function array_values;

/**
 * @internal
 * @covers \App\Console\Kernel
 */
class KernelTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testAllCommandsAreLazy(): void {
        // Possible?
        $kernel = $this->app->make(KernelContract::class);

        self::assertInstanceOf(Kernel::class, $kernel);

        // Lazy
        $artisan = (new class() extends Kernel {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public function getKernelArtisan(Kernel $kernel): Application {
                return $kernel->getArtisan();
            }
        })->getKernelArtisan($kernel);

        $lazyCommands = (new class() extends Application {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            /**
             * @return array<mixed>
             */
            public function getApplicationCommandMap(Application $application): array {
                return $application->commandMap;
            }
        })->getApplicationCommandMap($artisan);

        // All
        $allCommands = ClassMap::get()
            ->filter(static function (ReflectionClass $class): bool {
                return $class->isSubclassOf(Command::class)
                    && !$class->isAbstract()
                    && $class->getName() !== TestCommand::class
                    && $class->getName() !== ModelsCommand::class;
            })
            ->map(static function (ReflectionClass $class): string {
                return $class->getName();
            })
            ->all();

        // Test
        $all     = array_values($allCommands);
        $lazy    = array_values($lazyCommands);
        $invalid = array_values(array_diff($all, $lazy));

        self::assertEquals([], $invalid, 'Found commands which are not lazy!');
    }
}
