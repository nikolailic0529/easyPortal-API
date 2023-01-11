<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Commands;

use App\Services\Recalculator\Processor\Processors\LocationsProcessor;
use App\Utils\Eloquent\Events\Subject;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Recalculator\Commands\LocationsRecalculate
 */
class LocationsRecalculateTest extends TestCase {
    public function testInvoke(): void {
        $this->override(LocationsProcessor::class, function (): MockInterface {
            $mock = Mockery::mock(LocationsProcessor::class, [
                Mockery::mock(ExceptionHandler::class),
                $this->app->make(Dispatcher::class),
                $this->app->make(Repository::class),
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

    /**
     * @coversNothing
     */
    public function testHelp(): void {
        self::assertCommandDescription('ep:recalculator-locations-recalculate');
    }
}
