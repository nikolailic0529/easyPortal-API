<?php declare(strict_types = 1);

namespace App\Services\Search\Migrations;

use App\Services\Queue\CronJob;
use App\Services\Search\Eloquent\SearchableImpl;
use App\Services\Search\Service;
use App\Utils\Eloquent\Model;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Migrations\IndexesRebuild
 */
class IndexesRebuildTest extends TestCase {
    /**
     * @covers ::runRawUp
     */
    public function testRunRawUp(): void {
        $job       = (new class() extends CronJob {
            public function displayName(): string {
                return $this::class;
            }
        })::class;
        $model     = Mockery::mock(Model::class, SearchableImpl::class)::class;
        $migration = Mockery::mock(IndexesRebuild::class);
        $migration->shouldAllowMockingProtectedMethods();
        $migration->makePartial();

        $this->override(Service::class, static function (MockInterface $mock) use ($job, $model): void {
            $mock
                ->shouldReceive('getSearchableModels')
                ->once()
                ->andReturn([
                    $model,
                ]);
            $mock
                ->shouldReceive('getSearchableModelJob')
                ->once()
                ->andReturn($job);
        });

        Queue::fake();

        $migration->runRawUp();

        Queue::assertPushed($job, 1);
    }
}
