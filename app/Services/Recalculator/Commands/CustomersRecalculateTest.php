<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Commands;

use App\Services\Recalculator\Processor\Processors\CustomersProcessor;
use App\Utils\Eloquent\Events\Subject;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Recalculator\Commands\CustomersRecalculate
 */
class CustomersRecalculateTest extends TestCase {
    public function testInvoke(): void {
        $this->override(CustomersProcessor::class, function (): MockInterface {
            $mock = Mockery::mock(CustomersProcessor::class, [
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
            ->artisan('ep:recalculator-customers-recalculate')
            ->expectsOutput('Done.')
            ->assertSuccessful();
    }

    /**
     * @coversNothing
     */
    public function testHelp(): void {
        self::assertCommandDescription('ep:recalculator-customers-recalculate');
    }
}
