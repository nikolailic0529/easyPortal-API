<?php declare(strict_types = 1);

namespace App\Services\Search\Commands;

use App\Models\Customer;
use App\Services\Search\Processors\ModelsProcessor;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Search\Commands\IndexesRebuild
 */
class IndexesRebuildTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testCommand(): void {
        self::assertCommandDescription('ep:search-indexes-rebuild');
    }

    public function testInvoke(): void {
        $this->override(ModelsProcessor::class, static function (): MockInterface {
            $config     = Mockery::mock(Repository::class);
            $handler    = Mockery::mock(ExceptionHandler::class);
            $container  = Mockery::mock(Container::class);
            $dispatcher = Mockery::mock(Dispatcher::class);
            $processor  = Mockery::mock(ModelsProcessor::class, [$handler, $dispatcher, $config, $container]);
            $processor->makePartial();
            $processor
                ->shouldReceive('setModels')
                ->with([Customer::class])
                ->once()
                ->andReturnSelf();
            $processor
                ->shouldReceive('start')
                ->once()
                ->andReturn(true);

            return $processor;
        });

        $this
            ->artisan(
                'ep:search-indexes-rebuild',
                [
                    'model' => Customer::class,
                ],
            )
            ->expectsOutput('Done.')
            ->assertSuccessful();
    }
}
