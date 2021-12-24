<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Services\Search\Eloquent\Searchable as SearchSearchable;
use App\Services\Search\Jobs\UpdateIndexCronJob;
use Closure;
use Mockery;
use ReflectionClass;
use Tests\Helpers\Models;
use Tests\TestCase;

use function array_diff;
use function array_filter;
use function class_uses_recursive;
use function implode;
use function in_array;
use function is_a;

use const PHP_EOL;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Service
 */
class ServiceTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testAllSearchableModelRegistered(): void {
        $expected = Models::get()
            ->filter(static function (ReflectionClass $model): bool {
                return in_array(SearchSearchable::class, class_uses_recursive($model->getName()), true);
            })
            ->keys()
            ->all();
        $service  = $this->app->make(Service::class);
        $actual   = $service->getSearchableModels();
        $missed   = array_diff($expected, $actual);
        $invalid  = array_filter($actual, static function (string $model) use ($service): bool {
            return !is_a($service->getSearchableModelJob($model), UpdateIndexCronJob::class, true);
        });

        $this->assertEmpty(
            $missed,
            'Following models missed in $searchable:'.PHP_EOL.'- '.implode(PHP_EOL.'- ', $missed).PHP_EOL,
        );

        $this->assertEmpty(
            $invalid,
            'Following models has invalid associated Job:'.PHP_EOL.'- '.implode(PHP_EOL.'- ', $invalid).PHP_EOL,
        );
    }

    /**
     * @covers ::callWithoutIndexing
     */
    public function testCallWithoutIndexing(): void {
        $service = $this->app->make(Service::class);
        $model   = $this->faker->randomElement($service->getSearchableModels());
        $spy     = Mockery::spy(function () use ($model): void {
            $this->assertFalse($model::isSearchSyncingEnabled());
        });

        $this->assertTrue($model::isSearchSyncingEnabled());

        Service::callWithoutIndexing(Closure::fromCallable($spy));

        $this->assertTrue($model::isSearchSyncingEnabled());

        $spy->shouldHaveBeenCalled();
    }
}
