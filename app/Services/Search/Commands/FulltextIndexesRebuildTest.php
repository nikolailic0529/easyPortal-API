<?php declare(strict_types = 1);

namespace App\Services\Search\Commands;

use App\Models\Customer;
use App\Services\Search\Processors\FulltextsProcessor;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Commands\FulltextIndexesRebuild
 */
class FulltextIndexesRebuildTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testCommand(): void {
        self::assertCommandDescription('ep:search-fulltext-indexes-rebuild');
    }

    /**
     * @covers ::__invoke
     */
    public function testInvoke(): void {
        $this->override(FulltextsProcessor::class, static function (): MockInterface {
            $handler    = Mockery::mock(ExceptionHandler::class);
            $container  = Mockery::mock(Container::class);
            $dispatcher = Mockery::mock(Dispatcher::class);
            $processor  = Mockery::mock(FulltextsProcessor::class, [$handler, $dispatcher, $container]);
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
                'ep:search-fulltext-indexes-rebuild',
                [
                    'model' => Customer::class,
                ],
            )
            ->expectsOutput('Done.')
            ->assertSuccessful();
    }
}