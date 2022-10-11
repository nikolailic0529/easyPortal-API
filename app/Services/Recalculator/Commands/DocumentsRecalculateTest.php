<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Commands;

use App\Services\Recalculator\Processor\Processors\DocumentsProcessor;
use App\Utils\Eloquent\Events\Subject;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Recalculator\Commands\DocumentsRecalculate
 */
class DocumentsRecalculateTest extends TestCase {
    /**
     * @covers ::__invoke
     */
    public function testInvoke(): void {
        $this->override(DocumentsProcessor::class, function (): MockInterface {
            $mock = Mockery::mock(DocumentsProcessor::class, [
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
            ->artisan('ep:recalculator-documents-recalculate')
            ->expectsOutput('Done.')
            ->assertSuccessful();
    }

    /**
     * @coversNothing
     */
    public function testHelp(): void {
        self::assertCommandDescription('ep:recalculator-documents-recalculate');
    }
}
