<?php declare(strict_types = 1);

namespace App\Services\Search\Commands;

use App\Services\Search\Service;
use Exception;
use Mockery;
use Mockery\MockInterface;
use stdClass;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Commands\RebuildIndex
 */
class RebuildIndexTest extends TestCase {
    /**
     * @covers ::__invoke
     */
    public function testInvokeWithoutArgs(): void {
        $modelA = 'ModelA';
        $modelB = 'ModelB';
        $jobA   = 'JobA';
        $jobB   = 'JobB';
        $job    = Mockery::mock(stdClass::class);
        $job
            ->shouldReceive('dispatch')
            ->twice();

        $this->app->bind($jobA, static fn() => $job);
        $this->app->bind($jobB, static fn() => $job);

        $this->override(
            Service::class,
            static function (MockInterface $mock) use ($jobA, $jobB, $modelA, $modelB): void {
                $mock
                    ->shouldReceive('getSearchableModels')
                    ->once()
                    ->andReturn([
                        $modelA,
                        $modelB,
                    ]);
                $mock
                    ->shouldReceive('getSearchableModelJob')
                    ->with($modelA)
                    ->once()
                    ->andReturn($jobA);
                $mock
                    ->shouldReceive('getSearchableModelJob')
                    ->with($modelB)
                    ->once()
                    ->andReturn($jobB);
            },
        );

        $this
            ->artisan('ep:search-rebuild-index')
            ->assertSuccessful();
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeWithArgs(): void {
        $modelA = 'ModelA';
        $jobA   = 'JobA';
        $job    = Mockery::mock(stdClass::class);
        $job
            ->shouldReceive('dispatch')
            ->once();

        $this->app->bind($jobA, static fn() => $job);

        $this->override(
            Service::class,
            static function (MockInterface $mock) use ($jobA, $modelA): void {
                $mock
                    ->shouldReceive('getSearchableModels')
                    ->never();
                $mock
                    ->shouldReceive('getSearchableModelJob')
                    ->with($modelA)
                    ->once()
                    ->andReturn($jobA);
            },
        );

        $this
            ->artisan('ep:search-rebuild-index', [
                'models' => [$modelA],
            ])
            ->assertSuccessful();
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeError(): void {
        $model = 'Model';
        $job   = 'Job';

        $this->app->bind($job, static fn() => throw new Exception());

        $this->override(
            Service::class,
            static function (MockInterface $mock) use ($job, $model): void {
                $mock
                    ->shouldReceive('getSearchableModelJob')
                    ->with($model)
                    ->once()
                    ->andReturn($job);
            },
        );

        $this
            ->artisan('ep:search-rebuild-index', [
                'models' => [$model],
            ])
            ->assertFailed();
    }
}
