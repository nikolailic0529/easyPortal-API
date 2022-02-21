<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Commands;

use App\Services\Recalculator\Processor\Processors\CustomersProcessor;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Recalculator\Commands\CustomersRecalculate
 */
class CustomersRecalculateTest extends TestCase {
    /**
     * @covers ::__invoke
     */
    public function testInvoke(): void {
        $this->override(CustomersProcessor::class, function (): MockInterface {
            $mock = Mockery::mock(CustomersProcessor::class, [
                Mockery::mock(ExceptionHandler::class),
                $this->app->make(Dispatcher::class),
            ]);
            $mock->shouldAllowMockingProtectedMethods();
            $mock->makePartial();
            $mock
                ->shouldReceive('run')
                ->once()
                ->andReturns();

            return $mock;
        });

        $this
            ->artisan('ep:recalculator-customers-recalculate')
            ->expectsOutput('Done.')
            ->assertSuccessful();
    }
}
