<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Migrations;

use App\Services\Queue\CronJob;
use App\Services\Recalculator\Service;
use App\Utils\Eloquent\Model;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Recalculator\Migrations\Recalculate
 */
class RecalculateTest extends TestCase {
    /**
     * @covers ::runRawUp
     */
    public function testRunRawUp(): void {
        $job       = (new class() extends CronJob {
            public function displayName(): string {
                return $this::class;
            }
        })::class;
        $model     = Mockery::mock(Model::class)::class;
        $migration = Mockery::mock(Recalculate::class);
        $migration->shouldAllowMockingProtectedMethods();
        $migration->makePartial();

        $this->override(Service::class, static function (MockInterface $mock) use ($job, $model): void {
            $mock
                ->shouldReceive('getRecalculableModels')
                ->once()
                ->andReturn([
                    $model,
                ]);
            $mock
                ->shouldReceive('getRecalculableModelJob')
                ->once()
                ->andReturn($job);
        });

        Queue::fake();

        $migration->runRawUp();

        Queue::assertPushed($job, 1);
    }
}
