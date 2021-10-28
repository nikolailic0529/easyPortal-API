<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Elastic\SearchRequestFactory;
use App\Services\Search\GraphQL\ModelConverter;
use App\Services\Search\GraphQL\ScoutColumnResolver;
use App\Services\Search\Jobs\UpdateIndexJob;
use ElasticScoutDriver\Factories\SearchRequestFactoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\Builder as ScoutBuilder;
use Laravel\Scout\Scout;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Builders\Scout\ColumnResolver;
use Nuwave\Lighthouse\Schema\Source\SchemaSourceProvider;
use Nuwave\Lighthouse\Schema\TypeRegistry;
use Nuwave\Lighthouse\Testing\TestSchemaProvider;

class Provider extends ServiceProvider {
    public function register(): void {
        parent::register();

        $this->registerJobs();
        $this->registerBindings();
        $this->registerGraphqlTypes();
    }

    protected function registerJobs(): void {
        Scout::$makeSearchableJob   = UpdateIndexJob::class;
        Scout::$removeFromSearchJob = UpdateIndexJob::class;
    }

    protected function registerBindings(): void {
        $this->app->bind(ScoutBuilder::class, SearchBuilder::class);
        $this->app->bind(SearchRequestFactoryInterface::class, SearchRequestFactory::class);
        $this->app->bind(ColumnResolver::class, ScoutColumnResolver::class);
    }

    protected function registerGraphqlTypes(): void {
        $this->app->afterResolving(
            TypeRegistry::class,
            static function (TypeRegistry $types, Container $container): void {
                // Test schema?
                if ($container->make(SchemaSourceProvider::class) instanceof TestSchemaProvider) {
                    return;
                }

                // Convert
                $converter = $container->make(ModelConverter::class);

                foreach (Service::getSearchableModels() as $model) {
                    foreach ($converter->toInputObjectTypes($model) as $type) {
                        $types->register($type);
                    }
                }
            },
        );
    }
}
