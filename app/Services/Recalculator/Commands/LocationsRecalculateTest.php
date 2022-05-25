<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Commands;

use App\Services\Events\Eloquent\Subject;
use App\Services\Recalculator\Processor\Processors\LocationsProcessor;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Recalculator\Commands\LocationsRecalculate
 */
class LocationsRecalculateTest extends TestCase {
    /**
     * @covers ::__invoke
     */
    public function testInvoke(): void {
        $this->override(LocationsProcessor::class, function (): MockInterface {
            $mock = Mockery::mock(LocationsProcessor::class, [
                Mockery::mock(ExceptionHandler::class),
                $this->app->make(Dispatcher::class),
                Mockery::mock(Subject::class),
            ]);
            $mock->shouldAllowMockingProtectedMethods();
            $mock->makePartial();
            $mock
                ->shouldReceive('invoke')
                ->once()
                ->andReturns();

            return $mock;
        });

        $this
            ->artisan('ep:recalculator-locations-recalculate')
            ->expectsOutput('Done.')
            ->assertSuccessful();
    }
}
